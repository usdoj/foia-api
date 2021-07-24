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
        'Backlogged',
      ]);
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
      'Backlogged' => $this->t('Backlogged'),
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
    print_r($row);
    return FALSE;
    // Temporarily replacing: return parent::prepareRow($row);
  }

}
