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
   * The DOMDocument object.
   *
   * @var \DOMDocument
   */
  protected $document;

  /**
   * The root element of the DOMDocument object.
   *
   * @var \DOMElement
   */
  protected $root;

  /**
   * The node being processed.
   *
   * @var Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * Cast an ExportXml object to string.
   *
   * @return string
   *   An XML representation of the annual report.
   */
  public function __toString() {
    return $this->document->saveXML();
  }

  /**
   * Construct an ExportXml object with root element and header information.
   *
   * @param Drupal\node\Entity\Node $node
   *   A node of type annual_foia_report_data.
   */
  public function __construct(Node $node) {
    $this->node = $node;
    $date = date('Y-m-d');
    $snippet = <<<EOS
<?xml version="1.0"?>
<iepd:FoiaAnnualReport xmlns:iepd="http://leisp.usdoj.gov/niem/FoiaAnnualReport/exchange/1.03" xsi:schemaLocation="http://leisp.usdoj.gov/niem/FoiaAnnualReport/exchange/1.03 ../schema/exchange/FoiaAnnualReport.xsd" xmlns:foia="http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03" xmlns:i="http://niem.gov/niem/appinfo/2.0" xmlns:j="http://niem.gov/niem/domains/jxdm/4.1" xmlns:nc="http://niem.gov/niem/niem-core/2.0" xmlns:s="http://niem.gov/niem/structures/2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <nc:DocumentApplicationName nc:applicationVersionText="1.1">FOIA Annual Report Workbook</nc:DocumentApplicationName>
  <nc:DocumentCreationDate>
    <nc:Date>$date</nc:Date>
  </nc:DocumentCreationDate>
  <nc:DocumentDescriptionText>FOIA Annual Report</nc:DocumentDescriptionText>
</iepd:FoiaAnnualReport>
EOS;
    $this->document = new \DOMDocument('1.0');
    $this->document->loadXML($snippet);
    $this->root = $this->document->getElementsByTagNameNS('http://leisp.usdoj.gov/niem/FoiaAnnualReport/exchange/1.03', 'FoiaAnnualReport')[0];
  }

}
