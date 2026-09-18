<?php

namespace Drupal\foia_raw_data_to_report;

use Drupal\taxonomy\TermInterface;

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
   * Builds report metadata, organizations, and aggregated statute usage.
   *
   * @param \Drupal\taxonomy\TermInterface $agency
   *   The report's linked Agency term, supplying its name and abbreviation.
   * @param \Drupal\node\NodeInterface[] $components
   *   Components from the validated upload paragraphs, in paragraph order.
   * @param int $fiscal_year
   *   The report node's Year, already checked during CSV validation.
   * @param array $statutes
   *   Statute summaries returned by StatuteAggregator.
   *
   * @return string
   *   The serialized report XML.
   */
  public function build(TermInterface $agency, array $components, int $fiscal_year, array $statutes = []): string {
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

    // Use the Agency term, not the abbreviation on the raw data report node.
    $organization = $document->createElementNS(self::NAMESPACES['nc'], 'nc:Organization');
    $organization->setAttributeNS(self::NAMESPACES['s'], 's:id', 'ORG0');
    $root->appendChild($organization);
    $this->addOrganizationText($document, $organization, (string) $agency->get('field_agency_abbreviation')->value, $agency->label());

    // Only uploaded components are subunits, with IDs matching the example.
    $component_map = [];
    foreach (array_values($components) as $delta => $component) {
      $component_map[$component->id()] = 'ORG' . ($delta + 1);
      $subunit = $document->createElementNS(self::NAMESPACES['nc'], 'nc:OrganizationSubUnit');
      $subunit->setAttributeNS(self::NAMESPACES['s'], 's:id', 'ORG' . ($delta + 1));
      $organization->appendChild($subunit);
      $this->addOrganizationText($document, $subunit, (string) $component->get('field_agency_comp_abbreviation')->value, $component->label());
    }

    // Follow the Organization section with the report's selected fiscal year.
    $root->appendChild($document->createElementNS(self::NAMESPACES['foia'], 'foia:DocumentFiscalYearDate', (string) $fiscal_year));

    $this->addStatutes($document, $root, $statutes, $component_map);

    $xml = $document->saveXML();
    if ($xml === FALSE) {
      throw new \RuntimeException('Unable to serialize the raw data report XML.');
    }
    return $xml;
  }

  /**
   * Adds statute definitions, component counts, and agency-wide totals.
   */
  private function addStatutes(\DOMDocument $document, \DOMElement $root, array $statutes, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'Exemption3StatuteSection');
    $statutes = array_values($statutes);
    foreach ($statutes as $delta => $statute) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ReliedUponStatute');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'ES' . ($delta + 1));
      $this->addTextElement($document, $entry, 'j', 'StatuteDescriptionText', $statute['description']);
      // Keep distinct G and H values, separated by newlines across requests.
      $citations = implode("\n", $statute['citations']);
      $withheld = implode("\n", $statute['information_withheld']);
      $this->addTextElement($document, $entry, 'foia', 'ReliedUponStatuteInformationWithheldText', $withheld);
      $case = $this->addTextElement($document, $entry, 'nc', 'Case');
      // Match the example report when no case citation was supplied.
      $this->addTextElement($document, $case, 'nc', 'CaseTitleText', $citations === '' ? 'N/A' : $citations);
    }
    // Definitions precede associations, matching the existing annual exporter.
    foreach ($statutes as $delta => $statute) {
      $counts = [];
      foreach ($component_map as $component_id => $organization_id) {
        if (isset($statute['counts'][$component_id])) {
          $counts[$organization_id] = $statute['counts'][$component_id];
        }
      }
      $counts['ORG0'] = array_sum($counts);
      foreach ($counts as $organization_id => $quantity) {
        $association = $this->addTextElement($document, $section, 'foia', 'ReliedUponStatuteOrganizationAssociation');
        $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
        $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'ES' . ($delta + 1));
        $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
        $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
        $this->addTextElement($document, $association, 'foia', 'ReliedUponStatuteQuantity', (string) $quantity);
      }
    }
  }

  /**
   * Appends a namespaced element and safely escapes optional text.
   */
  private function addTextElement(\DOMDocument $document, \DOMElement $parent, string $prefix, string $name, string $text = ''): \DOMElement {
    $element = $document->createElementNS(self::NAMESPACES[$prefix], $prefix . ':' . $name);
    if ($text !== '') {
      $element->appendChild($document->createTextNode($text));
    }
    $parent->appendChild($element);
    return $element;
  }

  /**
   * Adds organization text safely, including names containing XML characters.
   */
  private function addOrganizationText(\DOMDocument $document, \DOMElement $parent, string $abbreviation, string $name): void {
    foreach (['OrganizationAbbreviationText' => $abbreviation, 'OrganizationName' => $name] as $element => $value) {
      $child = $document->createElementNS(self::NAMESPACES['nc'], 'nc:' . $element);
      $child->appendChild($document->createTextNode($value));
      $parent->appendChild($child);
    }
  }

}
