<?php

namespace Drupal\foia_upload_xml\Plugin\migrate;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\RequirementsInterface;

/**
 * Custom migration plugin for report xml upload processing.
 *
 * @package Drupal\foia_upload_xml\Plugin\migrate
 */
class FoiaUploadXmlMigration extends Migration {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    // Check whether the current migration source and destination plugin
    // requirements are met or not.
    if ($this->getSourcePlugin() instanceof RequirementsInterface) {
      $this->getSourcePlugin()->checkRequirements();
    }
    if ($this->getDestinationPlugin() instanceof RequirementsInterface) {
      $this->getDestinationPlugin()->checkRequirements();
    }

    if (empty($this->requirements)) {
      // There are no requirements to check.
      return;
    }
    /** @var \Drupal\migrate\Plugin\MigrationInterface[] $required_migrations */
    $required_migrations = $this->getMigrationPluginManager()->createInstances($this->requirements);

    $missing_migrations = array_diff($this->requirements, array_keys($required_migrations));
    // Check if the dependencies are in good shape.
    foreach ($required_migrations as $migration_id => $required_migration) {
      // Configures the source url of dependent migrations to use the same
      // source url as the current migration.  This ensures that when checking
      // that dependencies have run, the dependencies attempt to access data
      // in the correct, and existing, source file.
      $required_migration = $this->setMigrationSourceUrls($required_migration);
      if (!$required_migration->allRowsProcessed()) {
        $missing_migrations[] = $migration_id;
      }
    }
    if ($missing_migrations) {
      throw new RequirementsException('Missing migrations ' . implode(', ', $missing_migrations) . '.', ['requirements' => $missing_migrations]);
    }
  }

  /**
   * Set source urls of a migration to match the current migration's source url.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to set the source urls of.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The migration with updated source urls.
   */
  public function setMigrationSourceUrls(MigrationInterface $migration) {
    $source = $migration->getSourceConfiguration();
    $source['urls'] = $this->configuration['source']['urls'] ?? $source['urls'];
    $migration->set('source', $source);

    return $migration;
  }

}
