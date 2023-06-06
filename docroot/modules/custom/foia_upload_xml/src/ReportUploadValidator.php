<?php

namespace Drupal\foia_upload_xml;

use Drupal\node\Entity\Node;
use Drupal\file\FileInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Validates that an uploaded report can overwrite existing report data.
 *
 * @package Drupal\foia_upload_xml
 */
class ReportUploadValidator {

  use DependencySerializationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The report xml parser.
   *
   * @var \Drupal\foia_upload_xml\FoiaUploadXmlReportParser
   */
  protected $foiaUploadXmlReportParser;

  /**
   * ReportUploadValidator constructor.
   *
   * @param \Drupal\foia_upload_xml\FoiaUploadXmlReportParser $foiaUploadXmlReportParser
   *   The custom parser that gets the report year and agency abbreviation by
   *   default.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(FoiaUploadXmlReportParser $foiaUploadXmlReportParser, EntityTypeManager $entityTypeManager) {
    $this->foiaUploadXmlReportParser = $foiaUploadXmlReportParser;
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
    $file_data = $this->foiaUploadXmlReportParser->parse($file);
    $report_year = isset($file_data['report_year']) && !empty($file_data['report_year']) ? (int) $file_data['report_year'] : (int) date('Y');

    if (!$file_data['agency_tid']) {
      $form_state->setErrorByName('submit', \Drupal::translation()
        ->translate("The agency or component abbreviation in the XML does not match the abbreviation in FOIA.gov. Please ensure the agency and component abbreviations match those listed in FOIA.gov. If you are still having trouble, please contact OIP."));
    }

    if ($this->agencyReportYearIsLocked($file_data['agency_tid'], $report_year)) {
      $form_state->setErrorByName('submit', \Drupal::translation()
        ->translate("Your agency’s report has already been submitted. If you need to make changes to your agency’s report, please contact OIP."));
    }

    if ($this->agencyReportYearIsQueued($file_data['agency_tid'], $report_year)) {
      $form_state->setErrorByName('submit', \Drupal::translation()
        ->translate("Your agency already uploaded a report for the fiscal year. You may continue working on the existing report. Or you may start over by deleting the existing report before uploading a new XML"));
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
   * Check if the given agency and year already has an xml file in the queue.
   *
   * @param string $agency_tid
   *   The agency taxonomy term id to validate against.
   * @param int $year
   *   The annual report year to validate against.
   *
   * @return bool
   *   TRUE if a report is queued and this file should not be uploaded, FALSE
   *   if there is no report in the queue and this validation passes.
   */
  public function agencyReportYearIsQueued($agency_tid = NULL, $year = NULL) {
    if (!$agency_tid) {
      return FALSE;
    }

    $year = $year ? (int) $year : (int) date('Y');
    $queue_items = \Drupal::database()
      ->select('queue', 'q')
      ->fields('q')
      ->condition('name', 'foia_xml_report_import_worker', '=')
      ->execute();

    foreach ($queue_items as $queue_item) {
      $data = unserialize($queue_item->data);
      if ($data->agency == $agency_tid && $data->report_year == $year) {
        return TRUE;
      }
    }

    return FALSE;
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
      ->accessCheck(TRUE)
      ->condition('type', 'annual_foia_report_data')
      ->condition('field_agency', $agency_tid)
      ->condition('field_foia_annual_report_yr', $year);

    $nids = $node_query->execute();

    return !empty($nids) ? reset($nids) : FALSE;
  }

}
