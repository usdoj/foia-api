<?php

namespace Drupal\foia_export_xml;

use Drupal\node\Entity\Node;

/**
 * Class ExportXml.
 *
 * Generate an XML string from a node of type annual_foia_report_data.
 */
class ExportXml {

  /**
   * Export node as an XML string.
   *
   * @param Drupal\node\Entity\Node $node
   *   A node of type annual_foia_report_data.
   *
   * @return string
   *   An XML representation of the annual report.
   */
  public static function export(Node $node) {
    $title = $node->getTitle();
    $xml = '<?xml version="1.0"?>' . "\n<item>\n<nid>{$node->id()}</nid>\n<title>$title</title>\n</item>";
    return $xml;
  }

}
