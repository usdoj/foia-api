<?php

namespace Drupal\foia_upload_xml;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

/**
 * Class FoiaXmlUploadBatchImport.
 *
 * @package Drupal\foia_upload_xml
 *
 * Executes a predefined list of Migrations per Annual Report XML Upload.
 */
class FoiaXmlUploadBatchImport {

  /**
   * An array of migrations to be executed for XML import.
   *
   * @var array
   */
  protected $migrations;

  /**
   * FoiaXmlUploadBatchImport constructor.
   */
  public function __construct() {

  }

  /**
   * Fetches an array of migrations to run to import the Annual Report XML.
   *
   * @return array
   *   List of migrations.
   */
  public function getMigrationsList() {
    $migrations_list = [
      'component',
      'component_ix_personnel',
      'component_va_requests',
      'component_vb1_requests',
      'component_vb2_requests',
      'component_vb3_requests',
      'component_via_disposition',
      'component_vib_disposition',
      'component_vic1_applied_exemptions',
      'component_vic2_nonexemption_denial',
      'component_vic3_other_denial',
      'component_vic4_response_time',
      'component_viia_processed_requests',
      'component_viib_processed_requests',
      'component_viic1_simple_response',
      'component_viic2_complex_response',
      'component_viic3_expedited_response',
      'component_viid_pending_requests',
      'component_viie_oldest_pending',
      'component_viiia_expedited_processing',
      'component_viiib_fee_waiver',
      'component_xia_subsection_c',
      'component_xib_subsection_a2',
      'component_xiia',
      'component_xiib',
      'component_xiic',
      'component_xiid1',
      'component_xiid2',
      'component_xiie1',
      'component_xiie2',
      'component_x_fees',
      'foia_vb2_other',
      'foia_vic3_other',
      'foia_iv_details',
      'foia_iv_statute',
      'foia_va_requests',
      'foia_vb1_requests',
      'foia_vb2',
      'foia_vb3_requests',
      'foia_via_disposition',
      'foia_vib_disposition',
      'foia_vic1_applied_exemptions',
      'foia_vic2_nonexemption_denial',
      'foia_vic3',
      'foia_vic4_response_time',
      'foia_viia_processed_requests',
      'foia_viib_processed_requests',
      'foia_viic1_simple_response',
      'foia_viic2_complex_response',
      'foia_viic3_expedited_response',
      'foia_viid_pending_requests',
      'foia_viie_oldest_pending',
      'foia_viiia_expedited_processing',
      'foia_viiib_fee_waiver',
      'foia_ix_personnel',
      'foia_x_fees',
      'foia_xia_subsection_c',
      'foia_xib_subsection_a2',
      'foia_xiia',
      'foia_xiib',
      'foia_xiic',
      'foia_xiid1',
      'foia_xiid2',
      'foia_xiie1',
      'foia_xiie2',
      'foia_agency_report',
    ];

    return $migrations_list;
  }

  /**
   * Execute the migrations.
   */
  public function execMigrations(array $migrations_list) {
    $operations = [];
    foreach ($migrations_list as $migration_list_item) {
      $operations[] = ['\Drupal\foia_upload_xml\FoiaXmlUploadBatchImport::execImport', [$migration_list_item]];
    }
    $batch = [
      'title' => t('Importing Annual Report XML Data...'),
      'operations' => $operations,
      'init_message' => t('Commencing import'),
      'progress_message' => t('Imported @current out of @total'),
      'error_message' => t('An error occured during import'),
    ];

    batch_set($batch);

  }

  /**
   * Executes Migration's Import with Batch context.
   *
   * @param string $migration_list_item
   *   Migration ID.
   * @param array $context
   *   Batch Context.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function execImport($migration_list_item, array &$context) {
    drupal_set_message($migration_list_item . ' in progress.');
    $context['sandbox']['progress']++;
    $context['sandbox']['current_migration'] = $migration_list_item;

    $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_list_item);

    $executable = new MigrateExecutable($migration, new MigrateMessage());

    $executable->import();

    $context['message'] = $migration_list_item . ' processed.';
    $context['results'][] = $migration_list_item;
    drupal_set_message($migration_list_item . ' execution completed.');

  }

}
