<?php

namespace Drupal\foia_upload_xml\EventSubscriber;

use Drupal\migrate\Row;
use Drupal\Core\Utility\Error;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * FOIA Upload XML event subscriber.
 */
class FoiaUploadXmlMigrationPostImportSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Response event.
   */
  public function onKernelRequest(GetResponseEvent $event) {
    $this->messenger->addStatus(__FUNCTION__);
  }

  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Response event.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    $this->messenger->addStatus(__FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::POST_IMPORT => ['onPostAgencyImport'],
    ];
  }

  /**
   * Handle saving partial reports when report imports fail.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migrate import event.
   *
   * @see \Drupal\foia_upload_xml\FoiaUploadXmlMigrateExecutable::processRow()
   *   The foiaErrorInformation used to get and import the partially processed
   *   row is set when the MigrateException is caught in the
   *   FoiaUploadXmlMigrateExecutable::processRow() method.
   */
  public function onPostAgencyImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    if (!$migration || $migration->id() !== 'foia_agency_report') {
      return;
    }

    if (!property_exists($migration, 'foiaErrorInformation') || $migration->foiaErrorInformation['status'] !== MigrateIdMapInterface::STATUS_FAILED) {
      return;
    }

    $row = $migration->foiaErrorInformation['row'] ?? FALSE;
    if (!$row) {
      return;
    }

    $this->savePartial($migration, $row);
    $this->getEventDispatcher()->removeListener(MigrateEvents::POST_IMPORT, [$this, 'onPostAgencyImport']);
  }

  /**
   * Save the partial agency report row, maintaining the failed import status.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The failed migration.
   * @param \Drupal\migrate\Row $row
   *   The partially processed row, in the state it was in when the migration
   *   failed.
   *
   * @see \Drupal\migrate\MigrateExecutable::import()
   *   This implementation is based on the save process defined in the
   *   MigrateExecutable::import() method.
   */
  public function savePartial(MigrationInterface $migration, Row $row) {
    $id_map = $migration->getIdMap();
    $destination = $migration->getDestinationPlugin();

    try {
      $this->getEventDispatcher()->dispatch(MigrateEvents::PRE_ROW_SAVE, new MigratePreRowSaveEvent($migration, new MigrateMessage(), $row));
      $destination_ids = $id_map->lookupDestinationIds($row->getSourceIdValues());
      $destination_id_values = $destination_ids ? reset($destination_ids) : [];
      $destination_id_values = $destination->import($row, $destination_id_values);
      $this->getEventDispatcher()->dispatch(MigrateEvents::POST_ROW_SAVE, new MigratePostRowSaveEvent($migration, new MigrateMessage(), $row, $destination_id_values));
      $destination_id_values = $destination_id_values ?: [];
      $rollback_action = !empty($destination_id_values) ? $destination->rollbackAction() : NULL;
      // Save a mapping to the destination id values, while continuing to
      // indicate that the row failed.
      $id_map->saveIdMapping($row, $destination_id_values, MigrateIdMapInterface::STATUS_FAILED, $rollback_action);
    }
    catch (MigrateException $e) {
      $id_map->saveIdMapping($row, [], $e->getStatus());
      $id_map->saveMessage($row->getSourceIdValues(), $e->getMessage(), $e->getStatus());
    }
    catch (\Exception $e) {
      $id_map->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_FAILED);
      $result = Error::decodeException($e);
      $message = $result['@message'] . ' (' . $result['%file'] . ':' . $result['%line'] . ')';
      (new MigrateMessage)->display($message, 'error');
    }
  }

  /**
   * Gets the event dispatcher.
   *
   * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private function getEventDispatcher() {
    if (!$this->eventDispatcher) {
      $this->eventDispatcher = \Drupal::service('event_dispatcher');
    }
    return $this->eventDispatcher;
  }

}
