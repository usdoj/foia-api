<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Trait FieldInstallTrait.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
trait FieldInstallTrait {

  /**
   * Installs fields from config.
   *
   * @param array $fieldNames
   *   The fields to install.
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $configPath
   *   The path to config.
   */
  public function installFieldsOnEntity(array $fieldNames, $entityType, $bundle, $configPath = '/var/www/dojfoia/config/default') {
    foreach ($fieldNames as $fieldName) {
      $this->installFieldOnEntity($fieldName, $entityType, $bundle, $configPath);
    }
  }

  /**
   * Install a field from config.
   *
   * @param string $fieldName
   *   The field to install.
   * @param string $entityType
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $configPath
   *   The path to config.
   */
  public function installFieldOnEntity($fieldName, $entityType, $bundle, $configPath) {
    $yml = yaml_parse(file_get_contents($configPath . "/field.storage.{$entityType}.{$fieldName}.yml"));
    FieldStorageConfig::create($yml)->save();
    $yml = yaml_parse(file_get_contents($configPath . "/field.field.{$entityType}.{$bundle}.{$fieldName}.yml"));
    FieldConfig::create($yml)->save();
  }

}
