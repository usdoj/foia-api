<?php

/**
 * @file
 * Helper functions called by the Batch API.
 */

use Drupal\file\FileInterface;

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
function foia_upload_xml_execute_migration($migration_list_item, FileInterface $sourceFile, array &$context) {
  $batch_import = \Drupal::service('foia_upload_xml.batch_import');
  $batch_import->executeMigration($migration_list_item, $sourceFile, $context);
}

/**
 * Finishing script for batch execution.
 *
 * @param bool $success
 *   Flags success/failure of batch step.
 * @param array $results
 *   Results of batch step.
 */
function foia_upload_xml_execute_migration_finished($success, array $results) {
  $batch_import = \Drupal::service('foia_upload_xml.batch_import');
  $batch_import->executeMigrationFinished($success, $results);
  $lock = \Drupal::service('lock.persistent');
  $lock->release('foia_upload_xml');
}
