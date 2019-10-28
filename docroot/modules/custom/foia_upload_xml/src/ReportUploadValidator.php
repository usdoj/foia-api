<?php

namespace Drupal\foia_upload_xml;

use Drupal\node\Entity\Node;
use Drupal\file\FileInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Validates that an uploaded report can overwrite existing report data.
 *
 * @package Drupal\foia_upload_xml
 */
class ReportUploadValidator {

  /**
   * The data parser plugin manager.
   *
   * @var \Drupal\migrate_plus\DataParserPluginManager
   */
  protected $dataParserPluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The uploaded file.
   *
   * @var \Drupal\file\FileInterface
   */
  protected $file;

  /**
   * SimpleXml data parser configuration for fetching report data.
   *
   * @var array
   */
  protected $simpleXmlParserConfig = [
    'data_fetcher_plugin' => 'file',
    'namespaces' => [
      'iepd' => 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/exchange/1.03',
      'foia' => 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03',
      'nc' => 'http://niem.gov/niem/niem-core/2.0',
    ],
    'item_selector' => '/iepd:FoiaAnnualReport/foia:DocumentFiscalYearDate|/iepd:FoiaAnnualReport/nc:Organization/nc:OrganizationAbbreviationText',
    'fields' => [
      [
        'name' => 'report_year',
        'selector' => '/iepd:FoiaAnnualReport/foia:DocumentFiscalYearDate',
      ],
      [
        'name' => 'agency',
        'selector' => '/iepd:FoiaAnnualReport/nc:Organization/nc:OrganizationAbbreviationText',
      ],
    ],
    'ids' => [
      "report_year" => ['type' => 'integer'],
      "agency" => ['type' => 'string'],
    ],
  ];

  /**
   * ReportUploadValidator constructor.
   *
   * @param \Drupal\migrate_plus\DataParserPluginManager $dataParserPluginManager
   *   The data parser plugin manager service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(DataParserPluginManager $dataParserPluginManager, EntityTypeManager $entityTypeManager) {
    $this->dataParserPluginManager = $dataParserPluginManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Validate an uploaded report xml file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The uploaded report file.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object on which to set errors.
   */
  public function validate(FileInterface $file, FormStateInterface $form_state) {
    $this->file = $file;

    $file_data = $this->getFileData();
    $agency_tid = $this->getAgencyFromAbbreviation($file_data['agency'] ?? FALSE);
    $report_year = isset($file_data['report_year']) && !empty($file_data['report_year']) ? (int) $file_data['report_year'] : (int) date('Y');

    // If an agency can't be found, allow the upload to continue.
    if (!$agency_tid) {
      return;
    }

    if ($this->agencyReportYearIsLocked($agency_tid, $report_year)) {
      $form_state->setErrorByName('submit', \Drupal::translation()
        ->translate("Your agency’s report has already been submitted. If you need to make changes to your agency’s report, please contact OIP."));
    }
  }

  /**
   * Determine if an agency's current year report can be overwritten.
   *
   * @param int $agency_tid
   *   The taxonomy term id of the agency to check for existing reports.
   * @param int|null $year
   *   The 4 digit reporting year to check for existing reports or null for
   *   the current calendar year.
   *
   * @return bool
   *   Returns true if there is an existing report for this agency in the
   *   current year that should not be overwritten.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function agencyReportYearIsLocked($agency_tid, $year = NULL) {
    $year = $year ? $year : (int) date('Y');
    $report = $this->getReport($agency_tid, $year);
    if (!$report) {
      return FALSE;
    }

    $node = Node::load($report);
    try {
      return $this->reportIsLocked($node);
    }
    catch (MissingDataException $e) {
      // If an agency's current year report does not have a workflow state,
      // assume it can be overwritten.
      return FALSE;
    }
  }

  /**
   * Check if a report is in a workflow state in which it should not be updated.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The annual report data node that is being checked.
   *
   * @return bool
   *   Returns true if the given annual report is in a workflow state that
   *   indicates it should not be overwritten.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function reportIsLocked(Node $node) {
    return $node instanceof Node && $node->bundle() == 'annual_foia_report_data' && in_array($node->moderation_state->get(0)->value, [
      'submitted_to_oip',
      'cleared',
      'published',
    ]);
  }

  /**
   * Get data from the uploaded file using the SimpleXml parser.
   *
   * @return array
   *   Report data retrieved from the xpath configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getFileData() {
    $this->simpleXmlParserConfig['urls'] = [$this->file->getFileUri()];
    $simple_xml_parser = $this->dataParserPluginManager->createInstance('simple_xml', $this->simpleXmlParserConfig);

    $simple_xml_parser->rewind();
    $data = $simple_xml_parser->current();
    unset($simple_xml_parser);

    return $data;
  }

  /**
   * Lookup an agency taxonomy term id from the agency's abbreviated name.
   *
   * @param string $agency_abbreviation
   *   An agency's abbreviated name.
   *
   * @return bool|mixed
   *   The agency's taxonomy term id or FALSE if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAgencyFromAbbreviation($agency_abbreviation) {
    if (!$agency_abbreviation) {
      return FALSE;
    }

    $term_query = $this->entityTypeManager->getStorage('taxonomy_term')
      ->getQuery()
      ->condition('vid', 'agency')
      ->condition('field_agency_abbreviation', $agency_abbreviation);

    $tids = $term_query->execute();

    return !empty($tids) ? reset($tids) : FALSE;
  }

  /**
   * Get the nid of a report for the given agency and year, if one exists.
   *
   * @param int $agency_tid
   *   The taxonomy term id of the agency report to lookup.
   * @param int $year
   *   The 4 digit reporting year to check for existing reports or null for
   *   the current calendar year.
   *
   * @return bool|mixed
   *   The node id of the annual report data node that matches the parameters
   *   or false if none can be found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getReport($agency_tid, $year = NULL) {
    $year = $year ? $year : (int) date('Y');

    $node_query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('type', 'annual_foia_report_data')
      ->condition('field_agency', $agency_tid)
      ->condition('field_foia_annual_report_yr', $year);

    $nids = $node_query->execute();

    return !empty($nids) ? reset($nids) : FALSE;
  }

}
