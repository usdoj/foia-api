<?php

namespace Drupal\foia_upload_xml\Commands;

use Drupal\file\FileInterface;
use Drupal\Core\Database\Connection;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\foia_upload_xml\FoiaUploadXmlReportParser;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\foia_upload_xml\FoiaUploadXmlMigrationsProcessor;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile for the FoiaUploadXmlCommands.
 */
class FoiaUploadXmlCommands extends DrushCommands {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migrations processor.
   *
   * @var \Drupal\foia_upload_xml\FoiaUploadXmlMigrationsProcessor
   */
  protected $migrationsProcessor;

  /**
   * The report parser.
   *
   * @var \Drupal\foia_upload_xml\FoiaUploadXmlReportParser
   */
  protected $reportParser;

  /**
   * The user to set as the author during import.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $user;

  /**
   * FoiaUploadXmlCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger factory.
   * @param \Drupal\foia_upload_xml\FoiaUploadXmlMigrationsProcessor $migrationsProcessor
   *   The migrations processor.
   * @param \Drupal\foia_upload_xml\FoiaUploadXmlReportParser $reportParser
   *   The report parser.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerChannelFactory, FoiaUploadXmlMigrationsProcessor $migrationsProcessor, FoiaUploadXmlReportParser $reportParser) {
    parent::__construct();
    $this->connection = $database;
    $this->entityTypeManager = $entityTypeManager;
    $this->migrationsProcessor = $migrationsProcessor;
    $this->reportParser = $reportParser;
    $this->user = $this->entityTypeManager->getStorage('user')->load(1);
    $this->setLogger($loggerChannelFactory->get('foia_upload_xml'));
  }

  /**
   * Bulk import report xml files that are contained in a given directory.
   *
   * @param string $directory
   *   The path to the directory containing the report xml files.  This can
   *   be an absolute path on the server or a relative path from the site's
   *   docroot.
   *
   * @usage foia-upload-xml:bulk-upload sites/default/files/report-files
   *   Bulk import all report *.xml files that are in the directory
   *   /path/to/docroot/sites/default/files/report-files.
   *
   * @command foia-upload-xml:bulk-upload
   * @aliases fuxml
   * @bootstrap full
   */
  public function bulkUpload($directory) {
    $rows = [
      [
        'file' => 'File',
        'status' => 'Status',
      ],
    ];
    $files = $this->getXmlFiles($directory);
    foreach ($files as $filepath) {
      $info = pathinfo($filepath);
      if (!is_file($filepath)) {
        continue;
      }

      if (!is_readable($filepath)) {
        $this->logger()->warning(dt("Skipped @file: File not readable.", [
          '@file' => $info['basename'],
        ]));

        $rows[] = [
          'file' => $info['basename'],
          'status' => 'Skipped, file not readable',
        ];
        continue;
      }

      // This file does not need to be saved or moved, as each file will be
      // processed in place.  It is wrapped in a FileInterface class in order
      // to be used by the migrations processor.
      $source = $this->entityTypeManager->getStorage('file')->create([
        'uid' => 1,
        'status' => 0,
        'uri' => $filepath,
      ]);

      try {
        $this->migrationsProcessor->setSourceFile($source)
          ->setUser($this->user)
          ->processAll();

        $status = $this->migrationStatus($source);
        if ($status === MigrateIdMapInterface::STATUS_FAILED) {
          throw new \Exception(dt("The file @file was unable to be imported.", [
            '@file' => $filepath,
          ]));
        }

        // If the import is successful, set the moderation state to
        // cleared so that DoJ can check and then bulk publish all the imported
        // reports at once.
        $node = $this->migratedNode($source);
        if ($node) {
          $node->set('moderation_state', 'cleared');
          $node->save();
        }

        $rows[] = [
          'file' => $info['basename'],
          'status' => 'Processed',
        ];
      }
      catch (\Exception $e) {
        $this->logger()
          ->warning(dt("Foia Upload XML Bulk Upload: Failed to import @file.", [
            '@file' => $info['basename'],
          ]));
        $rows[] = [
          'file' => $info['basename'],
          'status' => 'Failed',
        ];
      }
    }

    return new RowsOfFields($rows);
  }

  /**
   * Get the xml files from a given directory.
   *
   * @param string $directory
   *   The directory path where the xml files live.
   *
   * @return array|false
   *   The xml files contained in the given directory, an empty array if none
   *   are found, or false on error.
   */
  protected function getXmlFiles($directory) {
    $realpath = realpath($directory) ?: $directory;
    return glob("$realpath/*.xml");
  }

  /**
   * Check the status of the annual report import by agency and report year.
   *
   * @param \Drupal\file\FileInterface $file
   *   The report's source xml file.
   *
   * @return int|false
   *   The value of the source_row_status column in the
   *   migrate_map_foia_agency_report table for the agency and report year
   *   of the given source file.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function migrationStatus(FileInterface $file) {
    $row = $this->migratedRow($file);
    return (int) $row['source_row_status'] ?? FALSE;
  }

  /**
   * Load the node that report data was migrated into.
   *
   * @param \Drupal\file\FileInterface $file
   *   The source xml file.
   *
   * @return bool|\Drupal\Core\Entity\EntityInterface|false
   *   The migration's destination node or false if it does not exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function migratedNode(FileInterface $file) {
    $row = $this->migratedRow($file);
    return $row['destid1']
      ? $this->entityTypeManager->getStorage('node')->load($row['destid1'])
      : FALSE;
  }

  /**
   * Get the status and destination id of the imported report.
   *
   * @param \Drupal\file\FileInterface $file
   *   The report's source xml file.
   *
   * @return array
   *   An array containing the source_row_status and destid1 values from the
   *   migrate_map_foia_agency_report table that correspond to the source file's
   *   agency and report year.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function migratedRow(FileInterface $file) {
    $report_data = $this->reportParser->parse($file);
    $agency = $report_data['agency'] ?? FALSE;
    $year = $report_data['report_year'] ?? date('Y');

    return $this->connection->select('migrate_map_foia_agency_report', 'm')
      ->fields('m', ['source_row_status', 'destid1'])
      ->condition('sourceid1', $year)
      ->condition('sourceid2', $agency)
      ->execute()
      ->fetchAssoc();
  }

  /**
   * Drop all XML-upload-related migrate tables. Use with caution!
   *
   * @usage foia-upload-xml:clean
   *   Clean (drop) all database tables related to these migrations.
   *
   * @command foia-upload-xml:clean
   * @aliases fuxml-clean
   * @bootstrap full
   */
  public function clean() {
    $tables = [
      'migrate_map_component',
      'migrate_map_component_iv_statutes',
      'migrate_map_component_ix_personnel',
      'migrate_map_component_va_requests',
      'migrate_map_component_vb1_requests',
      'migrate_map_component_vb2_requests',
      'migrate_map_component_vb3_requests',
      'migrate_map_component_via_disposition',
      'migrate_map_component_vib_disposition',
      'migrate_map_component_vic1_applied_exemptions',
      'migrate_map_component_vic2_nonexemption_denial',
      'migrate_map_component_vic3_other_denial',
      'migrate_map_component_vic4_response_time',
      'migrate_map_component_vic5_oldest_pending',
      'migrate_map_component_viia_processed_requests',
      'migrate_map_component_viib_processed_requests',
      'migrate_map_component_viic1_simple_response',
      'migrate_map_component_viic2_complex_response',
      'migrate_map_component_viic3_expedited_response',
      'migrate_map_component_viid_pending_requests',
      'migrate_map_component_viie_oldest_pending',
      'migrate_map_component_viiia_expedited_processing',
      'migrate_map_component_viiib_fee_waiver',
      'migrate_map_component_x_fees',
      'migrate_map_component_xia_subsection_c',
      'migrate_map_component_xib_subsection_a2',
      'migrate_map_component_xiia',
      'migrate_map_component_xiib',
      'migrate_map_component_xiic',
      'migrate_map_component_xiid1',
      'migrate_map_component_xiid2',
      'migrate_map_component_xiie1',
      'migrate_map_component_xiie2',
      'migrate_map_foia_agency_report',
      'migrate_map_foia_iv_details',
      'migrate_map_foia_iv_statute',
      'migrate_map_foia_ix_personnel',
      'migrate_map_foia_va_requests',
      'migrate_map_foia_vb1_requests',
      'migrate_map_foia_vb2',
      'migrate_map_foia_vb2_other',
      'migrate_map_foia_vb3_requests',
      'migrate_map_foia_via_disposition',
      'migrate_map_foia_vib_disposition',
      'migrate_map_foia_vic1_applied_exemptions',
      'migrate_map_foia_vic2_nonexemption_denial',
      'migrate_map_foia_vic3',
      'migrate_map_foia_vic3_other',
      'migrate_map_foia_vic4_response_time',
      'migrate_map_foia_vic5_oldest_pending',
      'migrate_map_foia_viia_processed_requests',
      'migrate_map_foia_viib_processed_requests',
      'migrate_map_foia_viic1_simple_response',
      'migrate_map_foia_viic2_complex_response',
      'migrate_map_foia_viic3_expedited_response',
      'migrate_map_foia_viid_pending_requests',
      'migrate_map_foia_viie_oldest_pending',
      'migrate_map_foia_viiia_expedited_processing',
      'migrate_map_foia_viiib_fee_waiver',
      'migrate_map_foia_x_fees',
      'migrate_map_foia_xia_subsection_c',
      'migrate_map_foia_xib_subsection_a2',
      'migrate_map_foia_xiia',
      'migrate_map_foia_xiib',
      'migrate_map_foia_xiic',
      'migrate_map_foia_xiid1',
      'migrate_map_foia_xiid2',
      'migrate_map_foia_xiie1',
      'migrate_map_foia_xiie2',
      'migrate_message_component',
      'migrate_message_component_iv_statutes',
      'migrate_message_component_ix_personnel',
      'migrate_message_component_va_requests',
      'migrate_message_component_vb1_requests',
      'migrate_message_component_vb2_requests',
      'migrate_message_component_vb3_requests',
      'migrate_message_component_via_disposition',
      'migrate_message_component_vib_disposition',
      'migrate_message_component_vic1_applied_exemptions',
      'migrate_message_component_vic2_nonexemption_denial',
      'migrate_message_component_vic3_other_denial',
      'migrate_message_component_vic4_response_time',
      'migrate_message_component_vic5_oldest_pending',
      'migrate_message_component_viia_processed_requests',
      'migrate_message_component_viib_processed_requests',
      'migrate_message_component_viic1_simple_response',
      'migrate_message_component_viic2_complex_response',
      'migrate_message_component_viic3_expedited_response',
      'migrate_message_component_viid_pending_requests',
      'migrate_message_component_viie_oldest_pending',
      'migrate_message_component_viiia_expedited_processing',
      'migrate_message_component_viiib_fee_waiver',
      'migrate_message_component_x_fees',
      'migrate_message_component_xia_subsection_c',
      'migrate_message_component_xib_subsection_a2',
      'migrate_message_component_xiia',
      'migrate_message_component_xiib',
      'migrate_message_component_xiic',
      'migrate_message_component_xiid1',
      'migrate_message_component_xiid2',
      'migrate_message_component_xiie1',
      'migrate_message_component_xiie2',
      'migrate_message_foia_agency_report',
      'migrate_message_foia_iv_details',
      'migrate_message_foia_iv_statute',
      'migrate_message_foia_ix_personnel',
      'migrate_message_foia_va_requests',
      'migrate_message_foia_vb1_requests',
      'migrate_message_foia_vb2',
      'migrate_message_foia_vb2_other',
      'migrate_message_foia_vb3_requests',
      'migrate_message_foia_via_disposition',
      'migrate_message_foia_vib_disposition',
      'migrate_message_foia_vic1_applied_exemptions',
      'migrate_message_foia_vic2_nonexemption_denial',
      'migrate_message_foia_vic3',
      'migrate_message_foia_vic3_other',
      'migrate_message_foia_vic4_response_time',
      'migrate_message_foia_vic5_oldest_pending',
      'migrate_message_foia_viia_processed_requests',
      'migrate_message_foia_viib_processed_requests',
      'migrate_message_foia_viic1_simple_response',
      'migrate_message_foia_viic2_complex_response',
      'migrate_message_foia_viic3_expedited_response',
      'migrate_message_foia_viid_pending_requests',
      'migrate_message_foia_viie_oldest_pending',
      'migrate_message_foia_viiia_expedited_processing',
      'migrate_message_foia_viiib_fee_waiver',
      'migrate_message_foia_x_fees',
      'migrate_message_foia_xia_subsection_c',
      'migrate_message_foia_xib_subsection_a2',
      'migrate_message_foia_xiia',
      'migrate_message_foia_xiib',
      'migrate_message_foia_xiic',
      'migrate_message_foia_xiid1',
      'migrate_message_foia_xiid2',
      'migrate_message_foia_xiie1',
      'migrate_message_foia_xiie2',
    ];
    foreach ($tables as $table) {
      $this->connection->schema()->dropTable($table);
    }
  }

}
