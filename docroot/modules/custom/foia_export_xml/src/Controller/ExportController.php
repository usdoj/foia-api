<?php

namespace Drupal\foia_export_xml\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\foia_export_xml\ExportXml;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ExportController for XML export.
 */
class ExportController extends ControllerBase {

  /**
   * Only provide a route for nodes of type annual_foia_report_data.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The result of the access check.
   */
  public function checkAccess(Node $node) {
    return AccessResult::allowedif($node->bundle() === 'annual_foia_report_data');
  }

  /**
   * Endpoint for the general public with simpler interface - published only.
   *
   * @param string $agency_abbreviation
   *   The agency abbreviation for the report.
   * @param string $year
   *   The year for the report.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The result of the access check.
   */
  public function checkAccessPublic($agency_abbreviation, $year) {

    $report = $this->getReportByAgencyAndYear($agency_abbreviation, $year);

    if (!empty($report) && !$report->isPublished()) {
      return AccessResult::forbidden();
    }
    else {
      return AccessResult::allowed();
    }
  }

  /**
   * Export node as an XML file.
   *
   * @param \Drupal\node\Entity\Node $node
   *   A node of type annual_foia_report_data.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object for download that contains the annual report's
   *   exported XML.
   */
  public function exportXml(Node $node) {
    return $this->convertReportToXmlResponse($node);
  }

  /**
   * Export node as an XML file.
   *
   * @param string $agency_abbreviation
   *   Presumably an agency abbreviation.
   * @param string $year
   *   Presumably a year.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object for download that contains the annual report's
   *   exported XML.
   */
  public function exportXmlPublic($agency_abbreviation, $year) {

    $report = $this->getReportByAgencyAndYear($agency_abbreviation, $year);

    if (!empty($report) && $report->isPublished()) {
      return $this->convertReportToXmlResponse($report);
    }
    else {
      throw new NotFoundHttpException();
    }
  }

  /**
   * Convert a Drupal node into an XML HTTP response.
   *
   * @param \Drupal\node\Entity\Node $report
   *   The Drupal node.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP reponse.
   */
  private function convertReportToXmlResponse(Node $report) {
    $export = new ExportXml($report);
    $response = new Response((string) $export);
    $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="annual-report.xml"');
    return $response;
  }

  /**
   * Get an annual report node by year and agency.
   *
   * @param string $agency_abbreviation
   *   Presumably an agency abbreviation.
   * @param string $year
   *   Presumably a year.
   *
   * @return \Drupal\node\Entity\Node
   *   The found report, or FALSE if none was found.
   */
  private function getReportByAgencyAndYear($agency_abbreviation, $year) {

    $agency_abbreviation = $this->sanitizeAgencyAbbreviation($agency_abbreviation);
    $year = $this->sanitizeYear($year);
    if (empty($agency_abbreviation) || empty($year)) {
      return FALSE;
    }

    $agencies = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'agency',
        'field_agency_abbreviation' => $agency_abbreviation,
      ]);
    if ($agency = reset($agencies)) {
      $reports = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadByProperties([
          'type' => 'annual_foia_report_data',
          'field_agency' => $agency->id(),
          'field_foia_annual_report_yr' => $year,
        ]);
      if ($report = reset($reports)) {
        return $report;
      }
    }

    return FALSE;
  }

  /**
   * Sanitize a user-input agency abbreviation.
   *
   * @param string $agency_abbreviation
   *   Presumably an agency abbreviation.
   *
   * @return string
   *   The sanitized abbreviation, but possibly an empty string.
   */
  private function sanitizeAgencyAbbreviation($agency_abbreviation) {
    $is_valid_agency_abbrev = preg_match('/^[A-Za-z\s\-]*$/i', $agency_abbreviation);
    if (!$is_valid_agency_abbrev) {
      return '';
    }
    return trim($agency_abbreviation);
  }

  /**
   * Sanitize a user-input year.
   *
   * @param string $year
   *   Presumably a year.
   *
   * @return string
   *   The sanitized year, but possibly an empty string.
   */
  private function sanitizeYear($year) {
    if (!is_numeric($year)) {
      return '';
    }
    return trim($year);
  }

}
