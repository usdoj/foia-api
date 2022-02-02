<?php

namespace Drupal\foia_upload_xml\FailedMigrationHandler;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\migrate\Plugin\migrate\process\SubProcess;

/**
 * A FailedMigrationHandler for report xml files missing sections.
 *
 * @package Drupal\foia_upload_xml\FailedMigrationHandler
 */
class SectionMissing extends DefaultHandler implements FailedMigrationHandlerInterface {

  /**
   * {@inheritDoc}
   */
  public function handle() {
    $definition = $this->extractSourceDefinition($this->exception);
    if (!$definition) {
      return parent::handle();
    }

    $sourceFile = pathinfo($this->migration->getSourceConfiguration()['urls']);
    $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS);
    $this->messenger->addError($this->t->translate('File @file failed: Missing selector @selector.', [
      '@file' => $sourceFile['basename'],
      '@selector' => $definition['selector'],
    ]));
  }

  /**
   * Get data about the field that the migration failed on.
   *
   * @param \Exception $e
   *   The migration exception.
   *
   * @return bool|mixed
   *   An array of data about the field that was being imported when the
   *   migration failed, or FALSE if it could not be found.
   *   Example:
   *   [
   *     'name' => 'component_xiic',
   *     'label' => 'Internal index of the agency component',
   *     'selector' =>
   *     'foia:OldestPendingConsultationSection/foia:OldestPendingItems/@s:id',
   *   ]
   *
   * @see migrate_plus.migration.foia_agency_report.yml
   */
  private function extractSourceDefinition(Exception $e) {
    // The first transform should be the Extract::transform() method.  The
    // pattern when importing section data is to have a sub process that
    // extracts a target id and target revision id from a source array.
    // This get's the most recent entry in the stack trace where the class
    // is the SubProcess class.  This then is able to get a field name that is
    // being imported since the subprocess will have the $destination_property
    // available, which can be used to find the source definition.
    $transforms = $this->getTransforms($e);
    $subprocessor = array_filter($transforms, function ($methodCall) {
      return $methodCall['class'] === SubProcess::class;
    });

    if (empty($subprocessor)) {
      return FALSE;
    }

    $subprocessor = reset($subprocessor);
    $field = end($subprocessor['args']);
    $source_definition = $this->getSourceDefinition($field);
    // The process field name does not always match the source field name.
    // When this is the case, the source field name has to be retrieved from
    // the process definition's first process, which will define a `source`
    // if it is different from the field name.  This source field can then
    // be used to get the source definition.
    if (empty($source_definition)) {
      $field = $this->getOriginalProcessSource($field);
      $source_definition = $this->getSourceDefinition($field);
    }

    return !empty($source_definition) ? reset($source_definition) : FALSE;
  }

  /**
   * Get the method calls to the transform function from the exception trace.
   *
   * @param \Exception $e
   *   The exception.
   *
   * @return array
   *   An array of method call information where the function call was to the
   *   transform() function.
   */
  private function getTransforms(Exception $e) {
    return array_filter($e->getTrace(), function ($methodCall) {
      return $methodCall['function'] === 'transform';
    });
  }

  /**
   * Get a field's source data definition from the migration configuration.
   *
   * @param string $field
   *   The source field name.
   *
   * @return array
   *   An array of source definitions whose name matches the field name given.
   */
  private function getSourceDefinition($field) {
    $fields = $this->migration->getSourceConfiguration()['fields'] ?? [];
    $source_definition = array_filter($fields, function ($definition) use ($field) {
      return $definition['name'] === $field;
    });
    return $source_definition;
  }

  /**
   * Get a field's source field name from the migration process configuration.
   *
   * @param string $field
   *   The name of the field being processed.
   *
   * @return string|bool
   *   The name of the data source field or FALSE if none is found.
   */
  private function getOriginalProcessSource($field) {
    $processors = $this->migration->getProcess()[$field];
    return array_reduce($processors, function ($sourceField, $process) {
      return $process['source'] ?? $sourceField;
    }, FALSE);
  }

}
