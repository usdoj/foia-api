<?php

namespace Drupal\foia_upload_xml;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

/**
 * Class FoiaUploadXmlBatchImport.
 *
 * @package Drupal\foia_upload_xml
 */
class FoiaUploadXmlBatchImport {

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
  public static function executeMigration($migration_list_item, array &$context) {
    \Drupal::messenger()->addMessage($migration_list_item . ' in progress.', 'self::TYPE_STATUS,');
    $context['sandbox']['current_migration'] = $migration_list_item;

    $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_list_item);
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();

    $context['message'] = $migration_list_item . ' processed.';
    $context['results'][] = $migration_list_item;
    \Drupal::messenger()->addMessage($migration_list_item . ' execution completed.', 'self::TYPE_STATUS,');

  }

  /**
   * Finishing script for batch execution.
   *
   * @param bool $success
   *   Flags success/failure of batch step.
   * @param array $results
   *   Results of batch step.
   * @param array $operations
   *   Batch step operations.
   */
  public function executeMigrationFinished($success, array $results, array $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One import step processed of @total.', '@count import steps processed of @total.');
    }
    else {
      $message = t('Finished with an error.');
    }

    \Drupal::messenger()->addMessage($message, 'self::TYPE_WARNING');

    // Providing data for the redirected page is done through $_SESSION.
    foreach ($results as $result) {
      \Drupal::messenger()->addMessage(('Processed @title.', ['@title' => $result]), 'self::TYPE_STATUS,');
    }
  }

}
