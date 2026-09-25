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
use Drupal\foia_raw_data_to_report\PersonnelAndCostAggregator;
use Drupal\foia_raw_data_to_report\SectionDataCsvValidator;
use Drupal\foia_raw_data_to_report\ReportNotifications;
use Drupal\foia_raw_data_to_report\UploadAssignments;
use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\foia_raw_data_to_report\StatuteAggregator;
use Drupal\foia_raw_data_to_report\RequestStatisticsAggregator;
use Drupal\foia_raw_data_to_report\ConsultationStatisticsAggregator;
use Drupal\foia_raw_data_to_report\DispositionAggregator;
use Drupal\foia_raw_data_to_report\OtherDenialReasonAggregator;
use Drupal\foia_raw_data_to_report\AppliedExemptionsAggregator;
use Drupal\foia_raw_data_to_report\AppealStatisticsAggregator;
use Drupal\foia_raw_data_to_report\AppealResponseTimeAggregator;
use Drupal\foia_raw_data_to_report\OldestPendingAppealAggregator;
use Drupal\foia_raw_data_to_report\OldestPendingRequestAggregator;
use Drupal\foia_raw_data_to_report\ExpeditedProcessingAggregator;
use Drupal\foia_raw_data_to_report\FeeWaiverAggregator;
use Drupal\foia_raw_data_to_report\FeesCollectedAggregator;
use Drupal\foia_raw_data_to_report\BacklogAggregator;
use Drupal\foia_raw_data_to_report\ProcessedResponseTimeAggregator;
use Drupal\foia_raw_data_to_report\PendingPerfectedRequestsAggregator;
use Drupal\foia_raw_data_to_report\AppealDispositionAggregator;
use Drupal\foia_raw_data_to_report\AppealNonExemptionDenialAggregator;
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityTypeManagerInterface $entityTypeManager, protected FileSystemInterface $fileSystem, protected FileRepositoryInterface $fileRepository, protected CsvValidator $csvValidator, protected ReportNotifications $notifications, protected SectionDataCsvValidator $sectionCsvValidator) {
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
      $container->get('foia_raw_data_to_report.notifications'),
      $container->get('foia_raw_data_to_report.section_csv_validator'),
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

      // Check the Section IX-XI file independently, even if raw data failed.
      $section_source = $supported ? $upload->get('section_ix_xi_data')->entity : NULL;
      $section_errors = [];
      if (!$section_source) {
        $section_errors[] = 'No Section IX-XI CSV file is attached. Please upload it again.';
      }
      elseif (strtolower(pathinfo($section_source->getFilename(), PATHINFO_EXTENSION)) !== 'csv') {
        $section_errors[] = 'Please upload a CSV file. Excel workbooks are not supported by this processor.';
      }
      else {
        $section_errors = $this->sectionCsvValidator->validate($section_source->getFileUri());
      }
      $has_errors = $has_errors || (bool) $section_errors;
      $section_prefix = sprintf('Upload %d — Component: %s — Section IX-XI file: %s', $delta + 1, $component?->label() ?? '(not selected)', $section_source?->getFilename() ?? '(not attached)');
      $messages[] = $section_prefix . "\n" . ($section_errors ? implode("\n", $section_errors) : 'CSV validated.');
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
      $this->notifications->record($data, 'validation_failed');
      return;
    }
    try {
      $this->generateXmlReport($node);
    }
    catch (\Throwable $exception) {
      // Reload persisted state: generation may have changed the XML field in
      // memory before failing. Save only messages alongside the stored file.
      $stored_node = $storage->loadUnchanged($node->id());
      if ($stored_node instanceof NodeInterface) {
        $validation_messages = (string) $stored_node->get('field_messages')->value;
        $stored_node->set('field_messages', [
          'value' => $validation_messages . "\n\nXML report processing failed: " . $exception->getMessage(),
          'format' => 'plain_text',
        ]);
        $stored_node->save();
      }
      $this->notifications->record($data, 'failed');
      // Preserve Drush logging and the queue's existing retry behavior.
      throw $exception;
    }
    $this->notifications->record($data, 'success');
  }

  /**
   * Builds and attaches report XML after all component CSVs pass validation.
   */
  protected function generateXmlReport(NodeInterface $node): void {
    $components = [];
    $sources = [];
    $section_sources = [];
    foreach ($node->get('field_component_uploads') as $item) {
      $component = $item->entity->get('field_agency_component')->entity;
      $components[] = $component;
      $section_sources[] = [
        'component_id' => $component->id(),
        'component_label' => $component->label(),
        'uri' => $item->entity->get('section_ix_xi_data')->entity->getFileUri(),
      ];
      $sources[] = [
        'component_id' => $component->id(),
        'component_label' => $component->label(),
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
    $appeal_dispositions = (new AppealDispositionAggregator())->aggregate($sources);
    // Column AC holds appeal exemptions; Column P remains request-only.
    $appeal_exemptions = (new AppliedExemptionsAggregator())->aggregate($sources, 28);
    $appeal_denials = (new AppealNonExemptionDenialAggregator())->aggregate($sources);
    $appeal_other_reasons = (new OtherDenialReasonAggregator())->aggregate($sources, 27);
    $appeal_response_times = (new AppealResponseTimeAggregator())->aggregate($sources, $fiscal_year);
    $oldest_pending_appeals = (new OldestPendingAppealAggregator())->aggregate($sources, $fiscal_year);
    $processed_response_times = (new ProcessedResponseTimeAggregator())->aggregate($sources);
    $information_granted_response_times = (new ProcessedResponseTimeAggregator())->aggregate($sources, TRUE);
    $simple_response_increments = (new ProcessedResponseTimeAggregator())->aggregateSimpleIncrements($sources);
    $complex_response_increments = (new ProcessedResponseTimeAggregator())->aggregateComplexIncrements($sources);
    $expedited_response_increments = (new ProcessedResponseTimeAggregator())->aggregateExpeditedIncrements($sources);
    $pending_perfected_requests = (new PendingPerfectedRequestsAggregator())->aggregate($sources, $fiscal_year);
    $oldest_pending_requests = (new OldestPendingRequestAggregator())->aggregate($sources, $fiscal_year);
    $expedited_processing = (new ExpeditedProcessingAggregator())->aggregate($sources);
    $fee_waivers = (new FeeWaiverAggregator())->aggregate($sources);
    $backlog = (new BacklogAggregator())->aggregate($sources, $fiscal_year);
    $consultation_statistics = (new ConsultationStatisticsAggregator())->aggregate($sources, $fiscal_year);
    $oldest_pending_consultations = (new OldestPendingRequestAggregator())->aggregate($sources, $fiscal_year, TRUE);
    $personnel_and_cost = (new PersonnelAndCostAggregator())->aggregate($section_sources);
    $fees_collected = (new FeesCollectedAggregator())->aggregate($personnel_and_cost);
    $xml = (new XmlReportBuilder())->build($node->get('field_agency')->entity, $components, $fiscal_year, $statutes, $request_statistics, $dispositions, $other_reasons, $applied_exemptions, $appeal_statistics, $appeal_dispositions, $appeal_exemptions, $appeal_denials, $appeal_other_reasons, $appeal_response_times, $oldest_pending_appeals, $processed_response_times, $information_granted_response_times, $simple_response_increments, $complex_response_increments, $expedited_response_increments, $pending_perfected_requests, $oldest_pending_requests, $expedited_processing, $fee_waivers, $fees_collected, $backlog, $consultation_statistics, $oldest_pending_consultations, $personnel_and_cost);
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
