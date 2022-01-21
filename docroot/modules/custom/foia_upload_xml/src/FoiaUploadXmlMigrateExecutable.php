<?php

namespace Drupal\foia_upload_xml;

use Drupal\migrate\Row;
use Drupal\migrate\MigrateException;
use Drupal\migrate_tools\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\migrate\process\Extract;
use Drupal\foia_upload_xml\FailedMigrationHandler\SectionMissing;
use Drupal\foia_upload_xml\FailedMigrationHandler\DefaultHandler;

/**
 * Wrapper around MigrateExecutable that provides for better failure messaging.
 *
 * @package Drupal\foia_upload_xml
 */
class FoiaUploadXmlMigrateExecutable extends MigrateExecutable {

  /**
   * {@inheritdoc}
   */
  public function processRow(Row $row, array $process = NULL, $value = NULL) {
    try {
      return parent::processRow($row, $process, $value);
    }
    catch (MigrateException $e) {
      // Handle special messaging if the migration that has failed is the
      // foia_agency_report migration and rethrow the exception so that the
      // import can otherwise continue as normal.
      $isFailedAgencyMigration = $this->migration->id() === 'foia_agency_report' && $e->getStatus() == MigrateIdMapInterface::STATUS_FAILED;
      if ($isFailedAgencyMigration) {
        $this->getFailureHandler($e)->handle();
        $this->migration->foiaErrorInformation = [
          'status' => $e->getStatus(),
          'row' => $this->migration->getSourcePlugin()->current(),
        ];
      }

      throw $e;
    }
  }

  /**
   * Get a FailedMigrationHandler based on the exception details.
   *
   * @param \Exception $e
   *   The exception.
   *
   * @return \Drupal\foia_upload_xml\FailedMigrationHandler\FailedMigrationHandlerInterface
   *   A FailedMigrationHandler that knows how to output messaging specific to
   *   this exception, or the DefaultHandler which will output a generic failed
   *   message.
   */
  protected function getFailureHandler($e) {
    $failedMethod = reset($e->getTrace());
    $container = \Drupal::getContainer();
    if ($failedMethod['class'] === Extract::class && $e->getMessage() == 'Input should be an array.') {
      return new SectionMissing($e, $container->get('messenger'), $this->migration, $container->get('string_translation'));
    }

    return new DefaultHandler($e, $container->get('messenger'), $this->migration, $container->get('string_translation'));
  }

}
