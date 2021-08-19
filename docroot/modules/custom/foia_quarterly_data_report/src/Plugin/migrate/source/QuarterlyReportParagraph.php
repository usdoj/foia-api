<?php

namespace Drupal\foia_quarterly_data_report\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Hello world.
 *
 * @MigrateSource(
 *   id = "quarterly_report_paragraph",
 *   source_module = "foia_quarterly_data_report",
 * )
 */
class QuarterlyReportParagraph extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this
      ->select('quarterlyrequest', 'qr')
      ->fields('qr', [
        'id',
        'Year',
        'Quarter',
        'Received',
        'Processed',
        'Backlog',
      ])
      ->fields('ag', [
        'Component',
        'USagency',
        'Level',
        'Description',
      ]);

    // For now we are only migrating 2021.
    $query->condition('Year', '2021');

    $query->join('agencies', 'ag', 'ag.id = qr.CompId');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => $this->t('id'),
      'Year' => $this->t('Year'),
      'Quarter' => $this->t('Quarter'),
      'Received' => $this->t('Received'),
      'Processed' => $this->t('Processed'),
      'Backlog' => $this->t('Backlog'),
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
        'alias' => 'qr',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $year = $row->getSourceProperty('Year');
    $level = $row->getSourceProperty('Level');

    $agency_abbreviation = $this->fixAgencyAbbreviation($row->getSourceProperty('USagency'));
    $component_abbreviation = $this->fixComponentAbbreviation($agency_abbreviation, $row->getSourceProperty('Component'));
    $agency_id = $this->getAgencyFromAbbreviation($agency_abbreviation);
    if (!$agency_id) {
      print('Unknown agency abbreviation: ' . $agency_abbreviation . PHP_EOL);
      return FALSE;
    }

    $component_id = FALSE;
    if ($level == '1') {
      // For level-1 we need to figure out if this is a centralized agency.
      $centralized_component_id = $this->getCentralizedAgencyComponent($agency_id);
      if ($centralized_component_id) {
        $component_id = $centralized_component_id;
      }
      else {
        // Skip decentralized agency overall numbers.
        // We'll get those later in QuarterlyReportNode.
        return FALSE;
      }
    }

    if (!$component_id) {
      $component_id = $this->getAgencyComponentFromAbbreviation($component_abbreviation, $agency_id);
    }

    if (!$component_id) {
      print('Unknown component abbreviation: ' . $component_abbreviation . PHP_EOL);
      print('  (in agency: ' . $agency_abbreviation . ')' . PHP_EOL);
      return FALSE;
    }

    $row->setSourceProperty('backlogged', $row->getSourceProperty('Backlog'));
    $row->setSourceProperty('received', $row->getSourceProperty('Received'));
    $row->setSourceProperty('processed', $row->getSourceProperty('Processed'));
    $row->setSourceProperty('agency_component', $component_id);

    return parent::prepareRow($row);
  }

  /**
   * Get the agency tid from the abbreviation.
   *
   * @param string $abbreviation
   *   The agency abbreviation.
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
   * Get the agency component nid from the abbreviation.
   *
   * @param string $abbreviation
   *   The agency component abbreviation.
   * @param int $agency_id
   *   The Drupal id of the agency that the component is in.
   *
   * @return int
   *   The node id of the agency component.
   */
  private function getAgencyComponentFromAbbreviation($abbreviation, $agency_id) {
    $components = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'agency_component',
        'field_agency' => $agency_id,
        'field_agency_comp_abbreviation' => $abbreviation,
      ]);
    if ($component = reset($components)) {
      return $component->id();
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

  /**
   * Fix a component abbreviation.
   *
   * @param string $agency_abbreviation
   *   The agency abbreviation.
   * @param string $component_abbreviation
   *   The component abbreviation.
   *
   * @return string
   *   The fixed abbreviation.
   */
  private function fixComponentAbbreviation($agency_abbreviation, $component_abbreviation) {
    $fixes = [
      'DOT' => [
        'SLSDC' => 'GLS',
      ],
    ];
    if (!empty($fixes[$agency_abbreviation][$component_abbreviation])) {
      return $fixes[$agency_abbreviation][$component_abbreviation];
    }
    return $component_abbreviation;
  }

  /**
   * Get the centralized component id of an agency, if it exists.
   *
   * @param int $agency_id
   *   The Drupal id of the agency that the component is in.
   *
   * @return int
   *   The node id of the agency component.
   */
  private function getCentralizedAgencyComponent($agency_id) {
    $components = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'type' => 'agency_component',
        'field_agency' => $agency_id,
        'status' => 1,
      ]);
    foreach ($components as $component) {
      $centralized = $component->field_is_centralized->value;
      if ($centralized) {
        return $component->id();
      }
    }
    return FALSE;
  }

}
