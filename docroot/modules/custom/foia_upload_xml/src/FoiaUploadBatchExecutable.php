<?php

namespace Drupal\foia_upload_xml;

use Drupal\migrate_tools\MigrateBatchExecutable;
use Drupal\migrate_plus\Entity\MigrationInterface;
use Drupal\migrate\MigrateMessageInterface;

/**
 * Class FoiaBatchExecutable.
 *
 * @package Drupal\foia_upload_xml
 *
 * Creates batch execution for migrations defined for Annual Report Import.
 */
class FoiaUploadBatchExecutable extends MigrateBatchExecutable {
  /**
   * Representing a batch import operation.
   */
  const BATCH_IMPORT = 1;

  /**
   * Indicates if we need to update existing rows or skip them.
   *
   * @var int
   */
  protected $updateExistingRows = 1;

  /**
   * Indicates if we need import dependent migrations also.
   *
   * @var int
   */
  protected $checkDependencies = 1;

  /**
   * The current batch context.
   *
   * @var array
   */
  protected $batchContext = [];

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message, array $options = []) {

    if (isset($options['update'])) {
      $this->updateExistingRows = $options['update'];
    }

    if (isset($options['force'])) {
      $this->checkDependencies = $options['force'];
    }

    parent::__construct($migration, $message, $options);
    $this->migrationPluginManager = \Drupal::getContainer()->get('plugin.manager.migration');
  }

  /**
   * Setup batch operations for running the migration.
   */
  public function batchImport() {
    // Create the batch operations for each migration that needs to be executed.
    // This includes the migration for this executable, but also the dependent
    // migrations.
    $this->itemLimit = 0;
    $this->updateExistingRows = 1;
    $this->checkDependencies = 1;

    $migrations = ['component', 'component_ix_personnel'];
    $operations = $this->batchOperations($migrations, 'import', [
      'limit' => $this->itemLimit,
      'update' => $this->updateExistingRows,
      'force' => $this->checkDependencies,
    ]);

    if (count($operations) > 0) {
      $batch = [
        'operations' => $operations,
        'title' => $this->t('Migrating %migrate', ['%migrate' => $this->migration->label()]),
        'init_message' => $this->t('Start migrating %migrate', ['%migrate' => $this->migration->label()]),
        'progress_message' => $this->t('Migrating %migrate', ['%migrate' => $this->migration->label()]),
        'error_message' => $this->t('An error occurred while migrating %migrate.', ['%migrate' => $this->migration->label()]),
        'finished' => '\Drupal\migrate_tools\MigrateBatchExecutable::batchFinishedImport',
      ];

      batch_set($batch);
    }
  }

}
