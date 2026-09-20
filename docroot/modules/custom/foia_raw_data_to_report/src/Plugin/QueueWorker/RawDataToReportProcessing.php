<?php

namespace Drupal\foia_raw_data_to_report\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\file\FileRepositoryInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\foia_raw_data_to_report\CsvValidator;
use Drupal\foia_raw_data_to_report\UploadAssignments;
use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\foia_raw_data_to_report\StatuteAggregator;
use Drupal\foia_raw_data_to_report\RequestStatisticsAggregator;
use Drupal\foia_raw_data_to_report\DispositionAggregator;
use Drupal\foia_raw_data_to_report\OtherDenialReasonAggregator;
use Drupal\foia_raw_data_to_report\AppliedExemptionsAggregator;
use Drupal\foia_raw_data_to_report\AppealStatisticsAggregator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes raw data reports through Drush, outside Drupal cron or pageload.
 *
 * @QueueWorker(
 *   id = "raw_data_to_report_processing",
 *   title = @Translation("Raw Data to Report Processing")
 * )
 */
final class RawDataToReportProcessing extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs the report queue worker.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityTypeManagerInterface $entityTypeManager, protected FileSystemInterface $fileSystem, protected FileRepositoryInterface $fileRepository, protected CsvValidator $csvValidator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('file.repository'),
      $container->get('foia_raw_data_to_report.csv_validator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!is_array($data) || empty($data['nid']) || !is_int($data['nid']) || $data['nid'] < 1) {
      throw new \InvalidArgumentException('A raw data report queue item must contain a positive integer nid.');
    }

    // Reload each item so a long-running worker uses the latest node data.
    $storage = $this->entityTypeManager->getStorage('node');
    $storage->resetCache([$data['nid']]);
    $node = $storage->load($data['nid']);
    // A node deleted while waiting in the queue no longer needs a report.
    if (!$node) {
      return;
    }
    if (!$node instanceof NodeInterface || $node->bundle() !== 'raw_data_to_report') {
      throw new \InvalidArgumentException('The queued node is not a raw data report.');
    }
    // Clear persisted messages before validating the current upload.
    $node->set('field_messages', []);
    $node->save();
    $assignment_errors = UploadAssignments::validate($node);
    $messages = [];
    $has_errors = FALSE;
    $agency = $node->get('field_agency')->entity;
    if (!$agency instanceof TermInterface) {
      $messages[] = 'Select an Agency before generating a report.';
      $has_errors = TRUE;
    }
    $uploaded_components = [];
    if ($node->get('field_component_uploads')->isEmpty()) {
      $messages[] = 'Add at least one Agency Component CSV upload before generating a report.';
      $has_errors = TRUE;
    }
    foreach ($node->get('field_component_uploads') as $delta => $item) {
      $upload = $item->entity;
      $errors = $assignment_errors[$delta] ?? [];
      $supported = $upload && $upload->bundle() === 'raw_data_component_upload';
      $component = $supported ? $upload->get('field_agency_component')->entity : NULL;
      $source = $supported ? $upload->get('field_request_data_csv')->entity : NULL;
      if ($component && $source) {
        $uploaded_components[$component->id()] = TRUE;
      }
      $prefix = sprintf('Upload %d — Component: %s — File: %s', $delta + 1, $component?->label() ?? '(not selected)', $source?->getFilename() ?? '(not attached)');
      if (!$source) {
        $errors[] = 'No CSV file is attached. Please upload a CSV file and try again.';
      }
      elseif (strtolower(pathinfo($source->getFilename(), PATHINFO_EXTENSION)) !== 'csv') {
        $errors[] = 'Please upload a CSV file. Excel workbooks are not supported by this processor.';
      }
      else {
        // Validate every file, even when another component's CSV has failed.
        $errors = array_merge($errors, $this->csvValidator->validate($source->getFileUri(), (int) $node->get('field_foia_annual_report_yr')->value));
      }
      $has_errors = $has_errors || (bool) $errors;
      $messages[] = $prefix . "\n" . ($errors ? implode("\n", array_unique($errors)) : 'CSV validated.');
    }
    // Compare against every linked component, regardless of publication/access.
    // Missing uploads are a warning only; CSV validation still controls errors.
    if ($agency instanceof TermInterface) {
      $component_ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'agency_component')
        ->condition('field_agency.target_id', $agency->id())
        ->sort('title')
        ->sort('nid')
        ->execute();
      $missing = $storage->loadMultiple(array_diff($component_ids, array_keys($uploaded_components)));
      if ($missing) {
        $names = array_map(static fn(NodeInterface $component) => $component->label(), $missing);
        $messages[] = 'Warning: no CSV has been attached for these Components: ' . implode(', ', $names);
      }
    }
    $node->set('field_messages', [
      'value' => ($has_errors ? "No new XML report was generated.\n\n" : '') . implode("\n\n", $messages),
      'format' => 'plain_text',
    ]);
    $node->save();
    if ($has_errors) {
      // Invalid input is a completed task, not a retryable exception.
      return;
    }
    $this->generateXmlReport($node);
  }

  /**
   * Builds and attaches report XML after all component CSVs pass validation.
   */
  protected function generateXmlReport(NodeInterface $node): void {
    $components = [];
    $sources = [];
    foreach ($node->get('field_component_uploads') as $item) {
      $component = $item->entity->get('field_agency_component')->entity;
      $components[] = $component;
      $sources[] = [
        'component_id' => $component->id(),
        'uri' => $item->entity->get('field_request_data_csv')->entity->getFileUri(),
      ];
    }
    $statutes = (new StatuteAggregator())->aggregate($sources);
    $fiscal_year = (int) $node->get('field_foia_annual_report_yr')->value;
    $request_statistics = (new RequestStatisticsAggregator())->aggregate($sources, $fiscal_year);
    $dispositions = (new DispositionAggregator())->aggregate($sources);
    $other_reasons = (new OtherDenialReasonAggregator())->aggregate($sources);
    $applied_exemptions = (new AppliedExemptionsAggregator())->aggregate($sources);
    $appeal_statistics = (new AppealStatisticsAggregator())->aggregate($sources, $fiscal_year);
    $xml = (new XmlReportBuilder())->build($node->get('field_agency')->entity, $components, $fiscal_year, $statutes, $request_statistics, $dispositions, $other_reasons, $applied_exemptions, $appeal_statistics);
    $field = $node->get('field_request_data_xml');
    $previous_file = $field->entity;
    $item = $field->first() ?? $field->appendItem();
    if (!$item instanceof FileItem) {
      throw new \RuntimeException('The report XML field must be a file field.');
    }
    $directory = $item->getUploadLocation(['node' => $node]);
    if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      throw new \RuntimeException('Unable to prepare the XML report directory.');
    }

    // Include the generation time so users can identify the latest report.
    $filename = 'raw-data-report-' . $node->id() . '-' . date('Y-m-d-H-i-s') . '.xml';
    $file = $this->fileRepository->writeData($xml, $directory . '/' . $filename, FileExists::Rename);
    $file->setOwnerId($node->getOwnerId());
    $file->save();
    $node->set('field_request_data_xml', ['target_id' => $file->id()]);
    // File field hooks manage permanence and usage when the node is saved.
    $node->save();

    // Remove the old managed file and its contents only after saving succeeds.
    if ($previous_file) {
      $previous_file->delete();
    }
  }

}
