<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Trait FieldInstallTrait installs fields from config.
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
    $fieldStorageConfig = yaml_parse(file_get_contents($configPath . "/field.storage.{$entityType}.{$fieldName}.yml"));
    $this->unsetAllowedValues($fieldStorageConfig);
    FieldStorageConfig::create($fieldStorageConfig)->save();
    $fieldConfig = yaml_parse(file_get_contents($configPath . "/field.field.{$entityType}.{$bundle}.{$fieldName}.yml"));
    FieldConfig::create($fieldConfig)->save();
  }

  /**
   * Unsets the allowed values list fields to bypass an unresolved core bug.
   *
   * @param array &$fieldStorageConfig
   *   The field's storage config.
   *
   * @see https://www.drupal.org/node/2802379
   */
  protected function unsetAllowedValues(array &$fieldStorageConfig) {
    if (isset($fieldStorageConfig['settings']['allowed_values'])) {
      unset($fieldStorageConfig['settings']['allowed_values']);
    }
  }

}
