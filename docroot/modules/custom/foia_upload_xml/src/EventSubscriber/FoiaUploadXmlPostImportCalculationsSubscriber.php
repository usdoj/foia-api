<?php

namespace Drupal\foia_upload_xml\EventSubscriber;

use Drupal\migrate\Row;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * FOIA Upload XML event subscriber.
 */
class FoiaUploadXmlPostImportCalculationsSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
  public function __construct(MessengerInterface $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      MigrateEvents::PRE_ROW_SAVE => ['onBeforeImportStatute'],
    ];
  }

  /**
   * Calculate section IV statute agency totals before saving the row.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The migrate pre row save event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function onBeforeImportStatute(MigratePreRowSaveEvent $event) {
    if ($event->getMigration()->id() !== 'foia_iv_statute') {
      return;
    }

    $row = $event->getRow();
    $componentData = $this->loadComponentParagraphs($row);
    $total = array_reduce($componentData, function ($sum, $component) {
      try {
        $addend = (int) $component->field_num_relied_by_agency_comp->value;
      }
      catch (\Exception $e) {
        $addend = 0;
      }

      return $sum + $addend;
    }, 0);

    $event->getRow()
      ->setDestinationProperty('field_total_num_relied_by_agency', $total);
  }

  /**
   * Load the component information paragraphs attached to the row being saved.
   *
   * @param \Drupal\migrate\Row $row
   *   The row being saved.
   *
   * @return array
   *   An array of paragraph entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function loadComponentParagraphs(Row $row) {
    $components = $row->getDestinationProperty('field_agency_component_inf');
    if (!is_array($components)) {
      return [];
    }

    $revision_ids = array_map(function ($component) {
      return $component['target_revision_id'];
    }, $components);
    $revision_ids = array_filter($revision_ids);

    return $this->entityTypeManager->getStorage('paragraph')
      ->loadMultipleRevisions($revision_ids);
  }

}
