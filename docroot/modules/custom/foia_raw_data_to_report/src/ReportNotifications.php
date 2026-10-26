<?php

namespace Drupal\foia_raw_data_to_report;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Stores queue outcomes for delivery to the user who requested generation.
 */
final class ReportNotifications {

  /**
   * Pending notices and retry-deduplication records expire after thirty days.
   */
  private const LIFETIME = 2592000;

  /**
   * Constructs the notification service.
   */
  public function __construct(protected KeyValueExpirableFactoryInterface $storeFactory, protected EntityTypeManagerInterface $entityTypeManager, protected AccountProxyInterface $currentUser, protected MessengerInterface $messenger, protected LockBackendInterface $lock, protected LoggerInterface $logger) {
  }

  /**
   * Records an outcome without changing the worker's success/retry behavior.
   */
  public function record(array $item, string $outcome): void {
    // Older queue items have no requester; do not guess a recipient.
    if (empty($item['requester_uid']) || empty($item['notification_id'])) {
      return;
    }
    try {
      $store = $this->storeFactory->get('foia_report_notifications.' . (int) $item['requester_uid']);
      $store->setWithExpireIfNotExists($item['notification_id'] . ':' . $outcome, [
        'nid' => $item['nid'],
        'job' => $item['notification_id'],
        'outcome' => $outcome,
        'delivered' => FALSE,
      ], self::LIFETIME);
    }
    catch (\Throwable $exception) {
      // A notification storage problem must not regenerate or discard XML.
      $this->logger->error('Unable to store report notification: @message', ['@message' => $exception->getMessage()]);
    }
  }

  /**
   * Transfers pending notices to the current user's Drupal message session.
   */
  public function deliver(): void {
    if (!$this->currentUser->isAuthenticated()) {
      return;
    }
    $uid = (int) $this->currentUser->id();
    $lock_name = 'foia_report_notifications.' . $uid;
    // Concurrent tabs must not deliver the same notice twice.
    if (!$this->lock->acquire($lock_name)) {
      return;
    }
    try {
      $store = $this->storeFactory->get($lock_name);
      foreach ($store->getAll() as $key => $notice) {
        if ($notice['delivered']) {
          continue;
        }
        // A successful retry supersedes an unread processing failure.
        $superseded = $notice['outcome'] === 'failed' && $store->has($notice['job'] . ':success');
        $node = $this->entityTypeManager->getStorage('node')->load($notice['nid']);
        if (!$superseded && $node instanceof NodeInterface && $node->bundle() === 'raw_data_to_report' && $node->access('view', $this->currentUser)) {
          $arguments = ['@report' => $node->toLink()->toString()];
          $message = match ($notice['outcome']) {
            'success' => new TranslatableMarkup('CSV to XML processing finished for @report.', $arguments),
            'validation_failed' => new TranslatableMarkup('CSV validation failed for @report. Review the report’s messages.', $arguments),
            default => new TranslatableMarkup('CSV to XML processing failed for @report. Review the report’s messages.', $arguments),
          };
          $this->messenger->addMessage($message, $notice['outcome'] === 'success' ? MessengerInterface::TYPE_STATUS : MessengerInterface::TYPE_ERROR);
        }
        // Retain a receipt to suppress notifications from retries of this job.
        $notice['delivered'] = TRUE;
        $store->setWithExpire($key, $notice, self::LIFETIME);
      }
    }
    catch (\Throwable $exception) {
      $this->logger->error('Unable to deliver report notification: @message', ['@message' => $exception->getMessage()]);
    }
    finally {
      $this->lock->release($lock_name);
    }
  }

}
