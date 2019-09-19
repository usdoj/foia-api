<?php

namespace Drupal\foia_upload_xml;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;

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
  public static function foia_upload_xml_batch($migration_list_item, array &$context) {
    drupal_set_message($migration_list_item . ' in progress.');
    $context['sandbox']['current_migration'] = $migration_list_item;

    $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_list_item);

    $executable = new MigrateExecutable($migration, new MigrateMessage());

    $executable->import();

    $context['message'] = $migration_list_item . ' processed.';
    $context['results'][] = $migration_list_item;
    drupal_set_message($migration_list_item . ' execution completed.');

  }

  /**
   * @param $success
   * @param $results
   * @param $operations
   */
  function foia_upload_xml_batch_finished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One import step processed of @total.', '@count import steps processed of @total.');
    }
    else {
      $message = t('Finished with an error.');
    }

    drupal_set_message($message);

    // Providing data for the redirected page is done through $_SESSION.
    foreach ($results as $result) {
      drupal_set_message(t('Processed @title.', array('@title' => $result)));
    }
  }

}
