<?php

namespace Drupal\foia_webform;

use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Log\LoggerInterface;

/**
 * Class FoiaSubmissionQueueingService.
 */
class FoiaSubmissionQueueingService implements FoiaSubmissionQueueingServiceInterface {

  /**
   * The core Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the Queueing service.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The core Queue Factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   The core Logger interface.
   */
  public function __construct(QueueFactory $queueFactory, LoggerInterface $logger) {
    $this->queueFactory = $queueFactory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function enqueue(FoiaRequestInterface $foiaRequest) {
    $foiaSubmissionsQueue = $this->queueFactory->get('foia_submissions');
    $submission = new \stdClass();
    $submission->id = $foiaRequest->id();
    $queueId = $foiaSubmissionsQueue->createItem($submission);
    $this->logQueuingResult($foiaRequest, $queueId);

    return $queueId;
  }

  /**
   * Log the result of the queueing attempt.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA Request entity.
   * @param mixed $query_id
   *   The queue ID if enqueueing was successful, else NULL.
   */
  protected function logQueuingResult(FoiaRequestInterface $foiaRequest, $query_id) {
    if ($query_id) {
      $this->logger
        ->info('FOIA request #%request_id added to queue.',
          [
            '%request_id' => $foiaRequest->id(),
            'link' => $foiaRequest->toLink(t('View'))->toString(),
          ]
        );
    }
    else {
      $this->logger
        ->info('Unable to enqueue FOIA Request ID: @id',
          [
            '@id' => $foiaRequest->id(),
          ]
        );
    }
  }

}
