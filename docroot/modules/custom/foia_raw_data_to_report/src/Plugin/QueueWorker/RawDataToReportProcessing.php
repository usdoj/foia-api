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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityTypeManagerInterface $entityTypeManager, protected FileSystemInterface $fileSystem, protected FileRepositoryInterface $fileRepository) {
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
    $this->generateXmlReport($node);
  }

  /**
   * Attaches an empty XML placeholder; future CSV conversion belongs here.
   */
  protected function generateXmlReport(NodeInterface $node): void {
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
    $file = $this->fileRepository->writeData('', $directory . '/' . $filename, FileExists::Rename);
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
