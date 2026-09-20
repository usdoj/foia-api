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
   * Builds metadata, organizations, statute usage, and request summaries.
   *
   * @param \Drupal\taxonomy\TermInterface $agency
   *   The report's linked Agency term, supplying its name and abbreviation.
   * @param \Drupal\node\NodeInterface[] $components
   *   Components from the validated upload paragraphs, in paragraph order.
   * @param int $fiscal_year
   *   The report node's Year, already checked during CSV validation.
   * @param array $statutes
   *   Statute summaries returned by StatuteAggregator.
   * @param array $request_statistics
   *   Component and overall summaries from RequestStatisticsAggregator.
   * @param array $dispositions
   *   Component and overall summaries from DispositionAggregator.
   * @param array $other_reasons
   *   Component and overall summaries from OtherDenialReasonAggregator.
   * @param array $applied_exemptions
   *   Component and overall summaries from AppliedExemptionsAggregator.
   *
   * @return string
   *   The serialized report XML.
   */
  public function build(TermInterface $agency, array $components, int $fiscal_year, array $statutes = [], array $request_statistics = [], array $dispositions = [], array $other_reasons = [], array $applied_exemptions = []): string {
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
    if ($request_statistics !== []) {
      $this->addRequestStatistics($document, $root, $request_statistics, $component_map);
    }
    if ($dispositions !== []) {
      $this->addDispositions($document, $root, $dispositions, $component_map);
    }
    if ($other_reasons !== []) {
      $this->addOtherDenialReasons($document, $root, $other_reasons, $component_map);
    }
    if ($applied_exemptions !== []) {
      $this->addAppliedExemptions($document, $root, $applied_exemptions, $component_map);
    }

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
   * Adds request counters and references to component/agency organizations.
   */
  private function addRequestStatistics(\DOMDocument $document, \DOMElement $root, array $statistics, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'ProcessedRequestSection');
    $fields = [
      'pending_start' => 'ProcessingStatisticsPendingAtStartQuantity',
      'received' => 'ProcessingStatisticsReceivedQuantity',
      'processed' => 'ProcessingStatisticsProcessedQuantity',
      'pending_end' => 'ProcessingStatisticsPendingAtEndQuantity',
    ];
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $statistics['components'][$component_id];
    }
    $organizations['ORG0'] = $statistics['overall'];

    // Keep matching suffixes: PS1 refers to ORG1, and PS0 to the agency ORG0.
    foreach ($organizations as $organization_id => $counts) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ProcessingStatistics');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'PS' . substr($organization_id, 3));
      foreach ($fields as $key => $name) {
        $this->addTextElement($document, $entry, 'foia', $name, (string) $counts[$key]);
      }
    }
    // Statistics precede associations, matching the example and exporter.
    foreach ($organizations as $organization_id => $counts) {
      $association = $this->addTextElement($document, $section, 'foia', 'ProcessingStatisticsOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'PS' . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds disposition counts, totals, and references to their organizations.
   */
  private function addDispositions(\DOMDocument $document, \DOMElement $root, array $dispositions, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'RequestDispositionSection');
    $fields = [
      1 => 'RequestDispositionFullGrantQuantity',
      2 => 'RequestDispositionPartialGrantQuantity',
      3 => 'RequestDispositionFullExemptionDenialQuantity',
    ];
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $dispositions['components'][$component_id];
    }
    $organizations['ORG0'] = $dispositions['overall'];
    foreach ($organizations as $organization_id => $counts) {
      $entry = $this->addTextElement($document, $section, 'foia', 'RequestDisposition');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'RD' . substr($organization_id, 3));
      foreach ($fields as $code => $name) {
        $this->addTextElement($document, $entry, 'foia', $name, (string) $counts[$code]);
      }
      // These values are XML reason codes, not the human-readable CSV labels.
      foreach (DispositionAggregator::NON_EXEMPTION_REASONS as $code => $reason) {
        $denial = $this->addTextElement($document, $entry, 'foia', 'NonExemptionDenial');
        $this->addTextElement($document, $denial, 'foia', 'NonExemptionDenialReasonCode', $reason);
        $this->addTextElement($document, $denial, 'foia', 'NonExemptionDenialQuantity', (string) $counts[$code]);
      }
      $this->addTextElement($document, $entry, 'foia', 'RequestDispositionTotalQuantity', (string) array_sum($counts));
    }
    // Keep RD/ORG suffixes aligned, including RD0 for the overall agency.
    foreach ($organizations as $organization_id => $counts) {
      $association = $this->addTextElement($document, $section, 'foia', 'RequestDispositionOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'RD' . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds free-text reason counts, totals, and organization references.
   */
  private function addOtherDenialReasons(\DOMDocument $document, \DOMElement $root, array $other_reasons, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'RequestDenialOtherReasonSection');
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $other_reasons['components'][$component_id];
    }
    $organizations['ORG0'] = $other_reasons['overall'];
    foreach ($organizations as $organization_id => $reasons) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ComponentOtherDenialReason');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'CODR' . substr($organization_id, 3));
      foreach ($reasons as $reason) {
        $item = $this->addTextElement($document, $entry, 'foia', 'OtherDenialReason');
        $this->addTextElement($document, $item, 'foia', 'OtherDenialReasonDescriptionText', $reason['description']);
        $this->addTextElement($document, $item, 'foia', 'OtherDenialReasonQuantity', (string) $reason['quantity']);
      }
      // The example sums usages, not the number of distinct reason texts.
      $total = array_sum(array_column($reasons, 'quantity'));
      $this->addTextElement($document, $entry, 'foia', 'ComponentOtherDenialReasonQuantity', (string) $total);
    }
    foreach ($organizations as $organization_id => $reasons) {
      $association = $this->addTextElement($document, $section, 'foia', 'OtherDenialReasonOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'CODR' . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds per-exemption counts and organization references, without a total.
   */
  private function addAppliedExemptions(\DOMDocument $document, \DOMElement $root, array $exemptions, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'RequestDispositionAppliedExemptionsSection');
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $exemptions['components'][$component_id];
    }
    $organizations['ORG0'] = $exemptions['overall'];
    foreach ($organizations as $organization_id => $counts) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ComponentAppliedExemptions');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'RDE' . substr($organization_id, 3));
      foreach (AppliedExemptionsAggregator::EXEMPTIONS as $code => $label) {
        // Omit unused exemptions for components and the agency overall.
        if ($counts[$code] === 0) {
          continue;
        }
        $item = $this->addTextElement($document, $entry, 'foia', 'AppliedExemption');
        $this->addTextElement($document, $item, 'foia', 'AppliedExemptionCode', $label);
        $this->addTextElement($document, $item, 'foia', 'AppliedExemptionQuantity', (string) $counts[$code]);
      }
    }
    foreach ($organizations as $organization_id => $counts) {
      $association = $this->addTextElement($document, $section, 'foia', 'ComponentAppliedExemptionsOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'RDE' . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
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
