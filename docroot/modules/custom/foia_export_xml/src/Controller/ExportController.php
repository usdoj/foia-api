<?php

namespace Drupal\foia_export_xml\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\foia_export_xml\ExportXml;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExportController.
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
    $export = new ExportXml($node);
    $response = new Response((string) $export);
    $response->headers->set('Content-Type', 'text/xml; charset=UTF-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="annual-report.xml"');
    return $response;
  }

}
