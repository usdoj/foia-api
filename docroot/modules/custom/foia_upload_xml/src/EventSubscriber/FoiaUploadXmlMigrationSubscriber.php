<?php

namespace Drupal\foia_upload_xml\EventSubscriber;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Respond to annual report import migration events.
 *
 * @package Drupal\foia_upload_xml\EventSubscriber
 */
class FoiaUploadXmlMigrationSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PRE_ROW_SAVE][] = ['updateMigrateMap'];

    return $events;
  }

  /**
   * Update the foia_agency_report migration map based on an existing node id.
   *
   * If there is an existing report node for the upload file's report_year
   * and agency abbreviation field, ensure that the
   * migrate_map_foia_agency_report table is up to date with that node's nid
   * as the destination, prior to running the foia_agency_report import.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The pre row save event.
   */
  public function updateMigrateMap(MigratePreRowSaveEvent $event) {
    if ($event->getMigration()->id() !== 'foia_agency_report') {
      return;
    }

    // If a report can't be found for the current import's report year and
    // agency, allow the import to continue without updating the migration
    // map table.  The assumption in this scenario is that a new Annual
    // Report node would be created.
    if (!$nids = $this->reportQuery($event)) {
      return;
    }

    // If the currently mapped destination id is a valid nid found by the
    // reportQuery() method, don't update the map table.  This prevents the
    // mapping from being changed if there happen to be multiple reports for
    // the same agency and year, and one of them is already mapped.
    $id_map = $event->getMigration()->getIdMap();
    $destination_ids = $id_map->lookupDestinationIds($event->getRow()
      ->getSourceIdValues());
    $destination_ids = $destination_ids ? reset($destination_ids) : [];
    if (!empty(array_intersect($nids, $destination_ids))) {
      return;
    }

    // If for some reason the map table is out of sync with the current
    // report node id value for the agency and year in question, it will be
    // brought back in sync by updating the map table here.
    // @see \Drupal\migrate\MigrateExecutable::import() at the line
    // $destination_ids = $id_map->lookupDestinationIds($this->sourceIdValues).
    $status = $event->getRow()->getIdMap()['source_row_status'] ?? MigrateIdMapInterface::STATUS_NEEDS_UPDATE;
    $rollback_action = $event->getRow()->getIdMap()['rollback_action'] ?? MigrateIdMapInterface::ROLLBACK_DELETE;
    $id_map->saveIdMapping($event->getRow(), [reset($nids)], $status, $rollback_action);
  }

  /**
   * Finds any Annual Report node associated with the import's year and agency.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The pre row save event.
   *
   * @return array|bool
   *   An array of node ids that match the report year and agency
   *   abbreviation for this upload file, or FALSE if none can be found.
   */
  protected function reportQuery(MigratePreRowSaveEvent $event) {
    $report_year = $event->getRow()->getSourceProperty('report_year');
    $agency = $event->getRow()->getSourceProperty('agency');

    if (!$report_year || !$agency) {
      return FALSE;
    }

    // First find the Agency taxonomy term corresponding to $agency.
    $taxonomy_query = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->getQuery()
      ->condition('vid', 'agency')
      ->condition('field_agency_abbreviation', $agency);
    $tids = $taxonomy_query->execute();

    if (empty($tids)) {
      return FALSE;
    }

    $node_query = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'annual_foia_report_data')
      ->condition('field_agency', reset($tids))
      ->condition('field_foia_annual_report_yr', $report_year);
    $node_query = $node_query->sort('created', 'DESC');

    $nids = $node_query->execute();

    return !empty($nids) ? $nids : FALSE;
  }

}
