<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Builds annual report XML independently of the node-based foia_export_xml.
 */
final class XmlReportBuilder {

  /**
   * Namespaces used by the annual report example and existing XML exporter.
   */
  private const NAMESPACES = [
    'iepd' => 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/exchange/1.03',
    'foia' => 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03',
    'i' => 'http://niem.gov/niem/appinfo/2.0',
    'j' => 'http://niem.gov/niem/domains/jxdm/4.1',
    'nc' => 'http://niem.gov/niem/niem-core/2.0',
    's' => 'http://niem.gov/niem/structures/2.0',
    'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
  ];

  /**
   * Builds the metadata stub; CSV-derived report sections will be added later.
   */
  public function build(): string {
    $document = new \DOMDocument('1.0', 'UTF-8');
    $document->formatOutput = TRUE;
    $root = $document->createElementNS(self::NAMESPACES['iepd'], 'iepd:FoiaAnnualReport');
    $document->appendChild($root);
    foreach (self::NAMESPACES as $prefix => $uri) {
      $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:' . $prefix, $uri);
    }
    $root->setAttributeNS(self::NAMESPACES['xsi'], 'xsi:schemaLocation', self::NAMESPACES['iepd'] . ' ../schema/exchange/FoiaAnnualReport.xsd');

    $application = $document->createElementNS(self::NAMESPACES['nc'], 'nc:DocumentApplicationName', 'FOIA Annual Report Workbook');
    $application->setAttributeNS(self::NAMESPACES['nc'], 'nc:applicationVersionText', '1.1');
    $root->appendChild($application);

    // Use the current generation date in Drupal's runtime timezone.
    $creation_date = $document->createElementNS(self::NAMESPACES['nc'], 'nc:DocumentCreationDate');
    $creation_date->appendChild($document->createElementNS(self::NAMESPACES['nc'], 'nc:Date', date('Y-m-d')));
    $root->appendChild($creation_date);
    $root->appendChild($document->createElementNS(self::NAMESPACES['nc'], 'nc:DocumentDescriptionText', 'FOIA Annual Report'));

    $xml = $document->saveXML();
    if ($xml === FALSE) {
      throw new \RuntimeException('Unable to serialize the raw data report XML.');
    }
    return $xml;
  }

}
