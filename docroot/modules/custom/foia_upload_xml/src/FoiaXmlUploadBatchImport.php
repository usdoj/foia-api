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
