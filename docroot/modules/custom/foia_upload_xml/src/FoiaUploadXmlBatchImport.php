<?php

namespace Drupal\foia_upload_xml;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationPluginManager;

/**
 * Class FoiaUploadXmlBatchImport.
 *
 * @package Drupal\foia_upload_xml
 */
class FoiaUploadXmlBatchImport {

use StringTranslationTrait;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The migration plugin manager.
   *
   * @var Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * Creates a FoiaUploadXmlBatchImport object.
   *
   * @param Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param Drupal\migrate\Plugin\MigrationPluginManager $migration_plugin_manager
   *   The migration plugin manager.
   */
  public function __construct(MessengerInterface $messenger, MigrationPluginManager $migration_plugin_manager) {
    $this->messenger = $messenger;
    $this->migrationPluginManager = $migration_plugin_manager;
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
  public function executeMigration($migration_list_item, array &$context) {
    \Drupal::messenger()->addStatus($migration_list_item . ' in progress.');
    $context['sandbox']['current_migration'] = $migration_list_item;

    $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_list_item);
    $migration->getIdMap()->prepareUpdate();
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();

    $context['message'] = $migration_list_item . ' processed.';
    $context['results'][] = $migration_list_item;
    \Drupal::messenger()->addStatus($migration_list_item . ' execution completed.');
  }

  /**
   * Finishing script for batch execution.
   *
   * @param bool $success
   *   Flags success/failure of batch step.
   * @param array $results
   *   Results of batch step.
   */
  public function executeMigrationFinished($success, array $results) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'One import step processed.', '@count import steps processed.');
      \Drupal::messenger()->addStatus($message);
    }
    else {
      $message = t('Finished with an error.');
      \Drupal::messenger()->addWarning($message);
    }

    // Providing data for the redirected page is done through $_SESSION.
    foreach ($results as $result) {
      $message = t('Processed @title.', ['@title' => $result]);
      \Drupal::messenger()->addStatus($message);
    }
  }

}
