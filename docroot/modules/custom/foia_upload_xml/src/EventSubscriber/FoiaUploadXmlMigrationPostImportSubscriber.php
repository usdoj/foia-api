<?php

namespace Drupal\foia_upload_xml\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Utility\Error;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * FOIA Upload XML event subscriber.
 */
class FoiaUploadXmlMigrationPostImportSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(MessengerInterface $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
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

    /** @var \Drupal\migrate\Row $row */
    $row = $migration->foiaErrorInformation['row'] ?? FALSE;
    if (!$row) {
      return;
    }

    // The listener is being run multiple times, but cannot be removed b/c
    // it has to work with the bulk uploader as well.  This ensures that
    // it only attempts to save the partial report once.
    $saved = &drupal_static(__FUNCTION__ . md5(implode('.', $row->getSourceIdValues())), FALSE);
    if ($saved) {
      return;
    }
    $this->savePartial($migration, $row);
    $saved = TRUE;
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
      $this->getEventDispatcher()
        ->dispatch(
          new MigratePreRowSaveEvent(
            $migration,
            new MigrateMessage(),
            $row),
        MigrateEvents::PRE_ROW_SAVE
        );
      $destination_ids = $id_map->lookupDestinationIds($row->getSourceIdValues());
      $destination_id_values = $destination_ids ? reset($destination_ids) : [];
      $destination_id_values = $destination->import($row, $destination_id_values);
      $this->getEventDispatcher()
        ->dispatch(
        new MigratePostRowSaveEvent($migration,
          new MigrateMessage(),
          $row,
          $destination_id_values),
        MigrateEvents::POST_ROW_SAVE,
        );
      $destination_id_values = $destination_id_values ?: [];
      $this->setPartialNodeModerationState($destination_id_values);
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
   *   Return Event Dispatcher service if it isn't already set
   */
  private function getEventDispatcher() {
    if (!$this->eventDispatcher) {
      $this->eventDispatcher = \Drupal::service('event_dispatcher');
    }
    return $this->eventDispatcher;
  }

  /**
   * Set the partially import node moderation state to "Draft".
   *
   * @param array $destination_ids
   *   An array of destination ids as returned from $destination->import().
   *   Only a single node id is expected.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function setPartialNodeModerationState(array $destination_ids = []) {
    if (empty($destination_ids)) {
      return;
    }

    $node = $this->entityTypeManager->getStorage('node')
      ->load(reset($destination_ids));
    if (!$node) {
      return;
    }

    $node->set('moderation_state', 'draft');
    $node->save();
  }

}
