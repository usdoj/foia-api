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
   * @param array $appeal_statistics
   *   Component and overall summaries from AppealStatisticsAggregator.
   * @param array $appeal_dispositions
   *   Component and overall summaries from AppealDispositionAggregator.
   * @param array $appeal_exemptions
   *   Column AC summaries from AppliedExemptionsAggregator.
   * @param array $appeal_denials
   *   Column AA summaries from AppealNonExemptionDenialAggregator.
   * @param array $appeal_other_reasons
   *   Column AB summaries from OtherDenialReasonAggregator.
   * @param array $appeal_response_times
   *   Component and overall summaries from AppealResponseTimeAggregator.
   * @param array $oldest_pending_appeals
   *   Component and overall lists from OldestPendingAppealAggregator.
   * @param array $processed_response_times
   *   Component and overall summaries from ProcessedResponseTimeAggregator.
   * @param array $information_granted_response_times
   *   Disposition-filtered summaries from ProcessedResponseTimeAggregator.
   * @param array $simple_response_increments
   *   Simple-track bin counts from ProcessedResponseTimeAggregator.
   *
   * @return string
   *   The serialized report XML.
   */
  public function build(TermInterface $agency, array $components, int $fiscal_year, array $statutes = [], array $request_statistics = [], array $dispositions = [], array $other_reasons = [], array $applied_exemptions = [], array $appeal_statistics = [], array $appeal_dispositions = [], array $appeal_exemptions = [], array $appeal_denials = [], array $appeal_other_reasons = [], array $appeal_response_times = [], array $oldest_pending_appeals = [], array $processed_response_times = [], array $information_granted_response_times = [], array $simple_response_increments = []): string {
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
      $this->addProcessingStatistics($document, $root, $request_statistics, $component_map, 'ProcessedRequestSection', 'PS');
    }
    if ($dispositions !== []) {
      $this->addDispositions($document, $root, $dispositions, $component_map);
    }
    if ($other_reasons !== []) {
      $this->addOtherDenialReasons($document, $root, $other_reasons, $component_map, 'RequestDenialOtherReasonSection', 'CODR');
    }
    if ($applied_exemptions !== []) {
      $this->addAppliedExemptions($document, $root, $applied_exemptions, $component_map, 'RequestDispositionAppliedExemptionsSection', 'RDE');
    }

    if ($appeal_statistics !== []) {
      $this->addProcessingStatistics($document, $root, $appeal_statistics, $component_map, 'ProcessedAppealSection', 'PA');
    }
    if ($appeal_dispositions !== []) {
      $this->addAppealDispositions($document, $root, $appeal_dispositions, $component_map);
    }

    if ($appeal_exemptions !== []) {
      $this->addAppliedExemptions($document, $root, $appeal_exemptions, $component_map, 'AppealDispositionAppliedExemptionsSection', 'ADE');
    }

    if ($appeal_denials !== []) {
      $this->addAppealNonExemptionDenials($document, $root, $appeal_denials, $component_map);
    }

    if ($appeal_other_reasons !== []) {
      $this->addOtherDenialReasons($document, $root, $appeal_other_reasons, $component_map, 'AppealDenialOtherReasonSection', 'ADOR');
    }

    if ($appeal_response_times !== []) {
      $this->addAppealResponseTimes($document, $root, $appeal_response_times, $component_map);
    }

    if ($oldest_pending_appeals !== []) {
      $this->addOldestPendingAppeals($document, $root, $oldest_pending_appeals, $component_map);
    }

    if ($processed_response_times !== []) {
      $this->addProcessedResponseTimes($document, $root, $processed_response_times, $component_map, 'ProcessedResponseTimeSection', 'PRT');
    }

    if ($information_granted_response_times !== []) {
      $this->addProcessedResponseTimes($document, $root, $information_granted_response_times, $component_map, 'InformationGrantedResponseTimeSection', 'IGRT');
    }

    if ($simple_response_increments !== []) {
      $this->addSimpleResponseTimeIncrements($document, $root, $simple_response_increments, $component_map);
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
   * Adds request or appeal counters and their organization references.
   */
  private function addProcessingStatistics(\DOMDocument $document, \DOMElement $root, array $statistics, array $component_map, string $section_name, string $prefix): void {
    $section = $this->addTextElement($document, $root, 'foia', $section_name);
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

    // Match organization suffixes: PS/PA1 links to ORG1, PS/PA0 to ORG0.
    foreach ($organizations as $organization_id => $counts) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ProcessingStatistics');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', $prefix . substr($organization_id, 3));
      foreach ($fields as $key => $name) {
        $this->addTextElement($document, $entry, 'foia', $name, (string) $counts[$key]);
      }
    }
    // Statistics precede associations, matching the example and exporter.
    foreach ($organizations as $organization_id => $counts) {
      $association = $this->addTextElement($document, $section, 'foia', 'ProcessingStatisticsOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', $prefix . substr($organization_id, 3));
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
  private function addOtherDenialReasons(\DOMDocument $document, \DOMElement $root, array $other_reasons, array $component_map, string $section_name, string $prefix): void {
    $section = $this->addTextElement($document, $root, 'foia', $section_name);
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $other_reasons['components'][$component_id];
    }
    $organizations['ORG0'] = $other_reasons['overall'];
    foreach ($organizations as $organization_id => $reasons) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ComponentOtherDenialReason');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', $prefix . substr($organization_id, 3));
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
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', $prefix . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds per-exemption counts and organization references, without a total.
   */
  private function addAppliedExemptions(\DOMDocument $document, \DOMElement $root, array $exemptions, array $component_map, string $section_name, string $prefix): void {
    $section = $this->addTextElement($document, $root, 'foia', $section_name);
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $exemptions['components'][$component_id];
    }
    $organizations['ORG0'] = $exemptions['overall'];
    foreach ($organizations as $organization_id => $counts) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ComponentAppliedExemptions');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', $prefix . substr($organization_id, 3));
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
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', $prefix . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds four appeal disposition counts, their total, and organization links.
   */
  private function addAppealDispositions(\DOMDocument $document, \DOMElement $root, array $dispositions, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'AppealDispositionSection');
    $fields = [
      'affirmed' => 'AppealDispositionAffirmedQuantity',
      'partial' => 'AppealDispositionPartialQuantity',
      'reversed' => 'AppealDispositionReversedQuantity',
      'other' => 'AppealDispositionOtherQuantity',
    ];
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $dispositions['components'][$component_id];
    }
    $organizations['ORG0'] = $dispositions['overall'];
    foreach ($organizations as $organization_id => $counts) {
      $entry = $this->addTextElement($document, $section, 'foia', 'AppealDisposition');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'AD' . substr($organization_id, 3));
      foreach ($fields as $key => $name) {
        $this->addTextElement($document, $entry, 'foia', $name, (string) $counts[$key]);
      }
      $this->addTextElement($document, $entry, 'foia', 'AppealDispositionTotalQuantity', (string) array_sum($counts));
    }
    foreach ($organizations as $organization_id => $counts) {
      $association = $this->addTextElement($document, $section, 'foia', 'AppealDispositionOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'AD' . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds appeal denial reason counts and their organization associations.
   */
  private function addAppealNonExemptionDenials(\DOMDocument $document, \DOMElement $root, array $denials, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'AppealNonExemptionDenialSection');
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $denials['components'][$component_id];
    }
    $organizations['ORG0'] = $denials['overall'];
    foreach ($organizations as $organization_id => $counts) {
      $entry = $this->addTextElement($document, $section, 'foia', 'AppealNonExemptionDenial');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'ANE' . substr($organization_id, 3));
      // Include all reason codes, including zero counts, as in the example.
      foreach (AppealNonExemptionDenialAggregator::REASONS as $code) {
        $reason = $this->addTextElement($document, $entry, 'foia', 'NonExemptionDenial');
        $this->addTextElement($document, $reason, 'foia', 'NonExemptionDenialReasonCode', $code);
        $this->addTextElement($document, $reason, 'foia', 'NonExemptionDenialQuantity', (string) $counts[$code]);
      }
    }
    foreach ($organizations as $organization_id => $counts) {
      $association = $this->addTextElement($document, $section, 'foia', 'AppealNonExemptionDenialOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'ANE' . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds response time statistics and their organization references.
   */
  private function addAppealResponseTimes(\DOMDocument $document, \DOMElement $root, array $statistics, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'AppealResponseTimeSection');
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $statistics['components'][$component_id];
    }
    $organizations['ORG0'] = $statistics['overall'];
    $fields = [
      'median' => 'ResponseTimeMedianDaysValue',
      'average' => 'ResponseTimeAverageDaysValue',
      'lowest' => 'ResponseTimeLowestDaysValue',
      'highest' => 'ResponseTimeHighestDaysValue',
    ];
    foreach ($organizations as $organization_id => $values) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ResponseTime');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'ART' . substr($organization_id, 3));
      foreach ($fields as $key => $name) {
        $value = $key === 'average' ? number_format($values[$key], 2, '.', '') : (string) $values[$key];
        $this->addTextElement($document, $entry, 'foia', $name, $value);
      }
    }
    foreach ($organizations as $organization_id => $values) {
      $association = $this->addTextElement($document, $section, 'foia', 'ResponseTimeOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'ART' . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds oldest open appeals and their organization references.
   */
  private function addOldestPendingAppeals(\DOMDocument $document, \DOMElement $root, array $appeals, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'OldestPendingAppealSection');
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $appeals['components'][$component_id];
    }
    $organizations['ORG0'] = $appeals['overall'];
    foreach ($organizations as $organization_id => $items) {
      $entry = $this->addTextElement($document, $section, 'foia', 'OldestPendingItems');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'OPA' . substr($organization_id, 3));
      foreach ($items as $item) {
        $old_item = $this->addTextElement($document, $entry, 'foia', 'OldItem');
        $this->addTextElement($document, $old_item, 'foia', 'OldItemReceiptDate', $item['receipt_date']);
        $this->addTextElement($document, $old_item, 'foia', 'OldItemPendingDaysQuantity', (string) $item['pending_days']);
      }
    }
    foreach ($organizations as $organization_id => $items) {
      $association = $this->addTextElement($document, $section, 'foia', 'OldestPendingItemsOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'OPA' . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds working-day statistics by track and organization associations.
   */
  private function addProcessedResponseTimes(\DOMDocument $document, \DOMElement $root, array $statistics, array $component_map, string $section_name, string $prefix): void {
    $section = $this->addTextElement($document, $root, 'foia', $section_name);
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $statistics['components'][$component_id];
    }
    $organizations['ORG0'] = $statistics['overall'];
    foreach ($organizations as $organization_id => $tracks) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ProcessedResponseTime');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', $prefix . substr($organization_id, 3));
      foreach (ProcessedResponseTimeAggregator::TRACKS as $track => $name) {
        $bin = $this->addTextElement($document, $entry, 'foia', $name);
        foreach ($tracks[$track] as $key => $value) {
          // Test the unrounded statistic before formatting the average.
          $suffix = $value < 1 ? 'Code' : 'Value';
          $text = $value < 1 ? 'LT1' : ($key === 'average' ? number_format($value, 2, '.', '') : (string) $value);
          $this->addTextElement($document, $bin, 'foia', 'ResponseTime' . ucfirst($key) . 'Days' . $suffix, $text);
        }
      }
    }
    foreach ($organizations as $organization_id => $tracks) {
      $association = $this->addTextElement($document, $section, 'foia', 'ProcessedResponseTimeOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', $prefix . substr($organization_id, 3));
      $organization = $this->addTextElement($document, $association, 'nc', 'OrganizationReference');
      $organization->setAttributeNS(self::NAMESPACES['s'], 's:ref', $organization_id);
    }
  }

  /**
   * Adds all thirteen simple response-time bins and organization references.
   */
  private function addSimpleResponseTimeIncrements(\DOMDocument $document, \DOMElement $root, array $increments, array $component_map): void {
    $section = $this->addTextElement($document, $root, 'foia', 'SimpleResponseTimeIncrementsSection');
    $organizations = [];
    foreach ($component_map as $component_id => $organization_id) {
      $organizations[$organization_id] = $increments['components'][$component_id];
    }
    $organizations['ORG0'] = $increments['overall'];
    foreach ($organizations as $organization_id => $counts) {
      $entry = $this->addTextElement($document, $section, 'foia', 'ComponentResponseTimeIncrements');
      $entry->setAttributeNS(self::NAMESPACES['s'], 's:id', 'SRT' . substr($organization_id, 3));
      foreach (ProcessedResponseTimeAggregator::INCREMENTS as $code => $upper) {
        $increment = $this->addTextElement($document, $entry, 'foia', 'TimeIncrement');
        $this->addTextElement($document, $increment, 'foia', 'TimeIncrementCode', $code);
        $this->addTextElement($document, $increment, 'foia', 'TimeIncrementProcessedQuantity', (string) $counts[$code]);
      }
      $this->addTextElement($document, $entry, 'foia', 'TimeIncrementTotalQuantity', (string) array_sum($counts));
    }
    foreach ($organizations as $organization_id => $counts) {
      $association = $this->addTextElement($document, $section, 'foia', 'ResponseTimeIncrementsOrganizationAssociation');
      $reference = $this->addTextElement($document, $association, 'foia', 'ComponentDataReference');
      $reference->setAttributeNS(self::NAMESPACES['s'], 's:ref', 'SRT' . substr($organization_id, 3));
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
