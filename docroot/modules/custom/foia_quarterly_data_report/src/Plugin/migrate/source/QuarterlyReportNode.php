<?php

namespace Drupal\foia_quarterly_data_report\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Hello world.
 *
 * @MigrateSource(
 *   id = "quarterly_report_node",
 *   source_module = "foia_quarterly_data_report",
 * )
 */
class QuarterlyReportNode extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this
      ->select('quarterlyoldest', 'qo')
      ->fields('qo', [
        'id',
        'CompId',
        'Year',
        'Quarter',
        'Oldest',
        'Closed',
      ])
      ->fields('ag', [
        'Component',
        'USagency',
        'Level',
        'Description',
      ]);

    // For now we are only migrating 2020.
    $query->condition('Year', '2021');

    $query->condition('Level', 1);
    $query->join('agencies', 'ag', 'ag.id = qo.CompId');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('id'),
      'CompId' => $this->t('CompId'),
      'Year' => $this->t('Year'),
      'Quarter' => $this->t('Quarter'),
      'Oldest' => $this->t('Oldest'),
      'Closed' => $this->t('Closed'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
        'alias' => 'qo',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $year = $row->getSourceProperty('Year');
    $quarter = $row->getSourceProperty('Quarter');

    $agency_abbreviation = $this->fixAgencyAbbreviation($row->getSourceProperty('USagency'));
    $agency_id = $this->getAgencyFromAbbreviation($agency_abbreviation);
    if (!$agency_id) {
      print('Unknown agency abbreviation: ' . $agency_abbreviation . PHP_EOL);
      return FALSE;
    }

    // Get the overall data.
    $legacy_id = $row->getSourceProperty('CompId');
    $query = $this->select('quarterlyrequest', 'qr');
    $query->fields('qr', ['Received', 'Processed', 'Backlog']);
    $query->condition('Year', $year);
    $query->condition('Quarter', $quarter);
    $query->condition('CompId', $legacy_id);
    $overall = $query->execute()->fetchAssoc();

    // Get all of the component data.
    $database = \Drupal::database();
    $paragraphs = [];
    $query = $this->select('quarterlyrequest', 'qr');
    $query->fields('qr', ['id', 'Year', 'Quarter', 'CompId']);
    $query->fields('ag', ['Level', 'USagency']);
    $query->join('agencies', 'ag', 'ag.id = qr.CompId');
    $query->condition('Year', $year);
    $query->condition('Quarter', $quarter);
    $query->condition('USagency', $row->getSourceProperty('USagency'));
    $result = $query->execute();
    foreach ($result as $record) {
      $legacy_id = $record['id'];
      $id_query = $database->select('migrate_map_quarterly_report_paragraph', 'mm');
      $id_query->condition('sourceid1', $legacy_id);
      $id_query->fields('mm', ['destid1', 'destid2']);
      $paragraph_ids = $id_query->execute()->fetchAssoc();
      if (!empty($paragraph_ids)) {
        $paragraphs[] = [
          'target_id' => $paragraph_ids['destid1'],
          'target_revision_id' => $paragraph_ids['destid2'],
        ];
      }
    }

    // Get all of the component node IDs.
    $component_ids = [];
    foreach ($paragraphs as $paragraph) {
      $entity = $entity = \Drupal::entityTypeManager()->getStorage('paragraph')->load($paragraph['target_id']);
      $component_id = $entity->get('field_agency_component')->target_id;
      $component_ids[$component_id] = TRUE;
    }
    $component_ids = array_keys($component_ids);

    $row->setSourceProperty('backlogged_oa', $overall['Backlog']);
    $row->setSourceProperty('processed_oa', $overall['Processed']);
    $row->setSourceProperty('received_oa', $overall['Received']);

    $row->setSourceProperty('component_data', $paragraphs);
    $row->setSourceProperty('components', $component_ids);

    $row->setSourceProperty('year', intval($year));
    $row->setSourceProperty('quarter', intval($quarter));
    $row->setSourceProperty('pending', $row->getSourceProperty('Oldest'));
    $row->setSourceProperty('closed', $row->getSourceProperty('Closed'));
    $row->setSourceProperty('agency', $agency_id);

    // Start published.
    $row->setSourceProperty('status', 1);
    $row->setSourceProperty('moderation_state', 'published');

    return parent::prepareRow($row);
  }

  /**
   * Get the agency tid from the abbreviation.
   *
   * @param string $abbreviation
   *   The abbreviation of the agency.
   *
   * @return int
   *   The taxonomy term id of the agency.
   */
  private function getAgencyFromAbbreviation($abbreviation) {
    $agencies = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'agency',
        'field_agency_abbreviation' => $abbreviation,
      ]);
    if ($agency = reset($agencies)) {
      return $agency->id();
    }
    return FALSE;
  }

  /**
   * Fix an agency abbreviation.
   *
   * @param string $abbreviation
   *   The agency abbreviation.
   *
   * @return string
   *   The fixed abbreviation.
   */
  private function fixAgencyAbbreviation($abbreviation) {
    $fixes = [
      'Ex-Im Bank' => 'EXIM Bank',
      'U.S. DOL' => 'DOL',
      'US RRB' => 'USRRB',
      'U.S. CPSC' => 'CPSC',
      'State' => 'DOS',
    ];
    if (!empty($fixes[$abbreviation])) {
      return $fixes[$abbreviation];
    }
    return $abbreviation;
  }

}
