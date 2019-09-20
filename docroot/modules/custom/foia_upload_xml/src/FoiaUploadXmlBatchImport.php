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
    $this->messenger->addStatus($migration_list_item . ' in progress.');
    $context['sandbox']['current_migration'] = $migration_list_item;

    $migration = $this->migrationPluginManager
      ->createInstance($migration_list_item);
    $migration->getIdMap()->prepareUpdate();
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $executable->import();

    $strings = ['@item' => $migration_list_item];
    $context['message'] = $this->t('@item processed.', $strings);
    $context['results'][] = $migration_list_item;
    $this->messenger->addStatus($this->t('@item execution completed.', $strings));
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
      $message = $this->formatPlural(
        count($results),
        'One import step processed.',
        '@count import steps processed.'
      );
      $this->messenger->addStatus($message);
    }
    else {
      $message = $this->t('Finished with an error.');
      $this->messenger->addWarning($message);
    }

    // Providing data for the redirected page is done through $_SESSION.
    foreach ($results as $result) {
      $message = $this->t('Processed @title.', ['@title' => $result]);
      $this->messenger->addStatus($message);
    }
  }

}
