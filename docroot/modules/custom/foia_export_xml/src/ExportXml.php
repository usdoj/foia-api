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
   * A map of component IDs to local identifiers.
   *
   * Keys are node IDs for agency_component nodes. Values are identifiers used
   * in the XML: "ORG1", "ORG2", etc.
   *
   * @var string
   */
  protected $componentMap = [];

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
    $date = $this->node->field_date_prepared->value;
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

    $this->organization();
    $this->exemption3StatuteSection();
    $this->processedRequestSection();
    $this->requestDispositionSection();
    $this->requestDenialOtherReasonSection();
    $this->requestDispositionAppliedExemptionsSection();
    $this->processedAppealSection();
    $this->appealDispositionSection();
    $this->appealDispositionAppliedExemptionsSection();
    $this->appealNonExemptionDenialSection();
    $this->appealDenialOtherReasonSection();
    $this->appealResponseTimeSection();
    $this->oldestPendingAppealSection();
    $this->processedResponseTimeSection();
    $this->informationGrantedResponseTimeSection();
    $this->simpleResponseTimeIncrementsSection();
    $this->complexResponseTimeIncrementsSection();
    $this->expeditedResponseTimeIncrementsSection();
    $this->pendingPerfectedRequestsSection();
    $this->oldestPendingRequestSection();
    $this->expeditedProcessingSection();
    $this->feeWaiverSection();
    $this->personnelAndCostSection();
    $this->feesCollectedSection();
    $this->subsectionUsedSection();
    $this->subsectionPostSection();
    $this->backlogSection();
    $this->processedConsultationSection();
    $this->oldestPendingConsultationSection();
    $this->processedRequestComparisonSection();
    $this->backloggedRequestComparisonSection();
    $this->processedAppealComparisonSection();
    $this->backloggedAppealComparisonSection();
  }

  /**
   * Add an element to the DOMDocument object.
   *
   * @param string $tag
   *   The tag name, in the format "prefix:localName".
   * @param \DOMElement $parent
   *   The parent of the new element.
   * @param string $value
   *   (optional) The text value of the new element.
   *
   * @return \DOMElement
   *   The newly added element.
   */
  protected function addElementNs($tag, \DOMElement $parent, $value = NULL) {
    $namespaces = [
      'iepd' => 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/exchange/1.03',
      'foia' => 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03',
      'i' => 'http://niem.gov/niem/appinfo/2.0',
      'j' => 'http://niem.gov/niem/domains/jxdm/4.1',
      'nc' => 'http://niem.gov/niem/niem-core/2.0',
      's' => 'http://niem.gov/niem/structures/2.0',
      'xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
    ];
    list($prefix, $local_name) = explode(':', $tag, 2);
    if (empty($namespaces[$prefix])) {
      throw new \Exception("Unrecognized prefix: $prefix");
    }
    $element = $this->document->createElementNS($namespaces[$prefix], $local_name, $value);
    $parent->appendChild($element);
    return $element;
  }

  /**
   * Add component data.
   *
   * Add data from an array of paragraphs with per-component data and
   * corresponding overall data from the node.
   *
   * @param EntityInterface[] $component_data
   *   An array of paragraphs with per-component data, each with
   *   field_agency_component referencing an Agency Component node.
   * @param \DOMElement $parent
   *   The parent element to which new nodes will be added.
   * @param string $data_tag
   *   The XML tag of the data section.
   * @param string $prefix
   *   The base string used in the s:id attribute.
   * @param string[] $map
   *   An array mapping fields on the paragraphs to XML tags.
   * @param string[] $overall_map
   *   An array mapping fields on the node to XML tags.
   */
  protected function addComponentData(array $component_data, \DOMElement $parent, $data_tag, $prefix, array $map, array $overall_map) {
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs($data_tag, $parent);
      $item->setAttribute('s:id', $prefix . ($delta + 1));
      foreach ($map as $field => $tag) {
        $this->addElementNs($tag, $item, $component->get($field)->value);
      }
    }

    // Add overall data.
    $item = $this->addElementNs($data_tag, $parent);
    $item->setAttribute('s:id', $prefix . '0');
    foreach ($overall_map as $field => $tag) {
      $this->addElementNs($tag, $item, $this->node->get($field)->value);
    }
  }

  /**
   * Add processing associations.
   *
   * Add associations between per-section identifiers and per-report identifiers
   * for components.
   *
   * @param EntityInterface[] $component_data
   *   An array of paragraphs with per-component data, each with
   *   field_agency_component referencing an Agency Component node.
   * @param \DOMElement $parent
   *   The parent element to which new nodes will be added.
   * @param string $tag
   *   The XML tag of the association section.
   * @param string $prefix
   *   The base string used in the s:ref attribute.
   */
  protected function addProcessingAssociations(array $component_data, \DOMElement $parent, $tag, $prefix) {
    // Add processing association for each component.
    foreach ($component_data as $delta => $component) {
      $agency_component = $component->field_agency_component->referencedEntities()[0];
      $matchup = $this->addElementNs($tag, $parent);
      $this
        ->addElementNs('foia:ComponentDataReference', $matchup)
        ->setAttribute('s:ref', $prefix . ($delta + 1));
      $this
        ->addElementNs('nc:OrganizationReference', $matchup)
        ->setAttribute('s:ref', $this->componentMap[$agency_component->id()]);
    }

    // Add processing association for the agency overall.
    $matchup = $this->addElementNs($tag, $parent);
    $this
      ->addElementNs('foia:ComponentDataReference', $matchup)
      ->setAttribute('s:ref', $prefix . 0);
    $this
      ->addElementNs('nc:OrganizationReference', $matchup)
      ->setAttribute('s:ref', 'ORG' . 0);
  }

  /**
   * Agency Information.
   *
   * This corresponds to the Agency Information section of the annual report.
   */
  protected function organization() {
    $agency = $this->node->field_agency->referencedEntities()[0];

    // Add abbreviation and name for the agency.
    $org = $this->addElementNs('nc:Organization', $this->root);
    $org->setAttribute('s:id', 'ORG0');
    $item = $this->addElementNs('nc:OrganizationAbbreviationText', $org, $agency->field_agency_abbreviation->value);
    $item = $this->addElementNs('nc:OrganizationName', $org, $agency->label());

    // Add abbreviation and name for each component and populate
    // $this->componentMap.
    foreach ($this->node->field_agency_components->referencedEntities() as $delta => $component) {
      $local_id = 'ORG' . ($delta + 1);
      $this->componentMap[$component->id()] = $local_id;

      $suborg = $this->addElementNs('nc:OrganizationSubUnit', $org);
      $suborg->setAttribute('s:id', $local_id);
      $item = $this->addElementNs('nc:OrganizationAbbreviationText', $suborg, $component->field_agency_comp_abbreviation->value);
      $item = $this->addElementNs('nc:OrganizationName', $suborg, $component->label());
    }

    // Add the fiscal year.
    $this->addElementNs('foia:DocumentFiscalYearDate', $this->root, $this->node->field_foia_annual_report_yr->value);
  }

  /**
   * Exemption 3 Statutes.
   *
   * This corresponds to Section IV of the annual report.
   */
  protected function exemption3StatuteSection() {
    // @todo
  }

  /**
   * Received, Processed and Pending FOIA Requests.
   *
   * This corresponds to Section V.A of the annual report.
   */
  protected function processedRequestSection() {
    $component_data = $this->node->field_foia_requests_va->referencedEntities();
    $map = [
      'field_req_pend_start_yr' => 'foia:ProcessingStatisticsPendingAtStartQuantity',
      'field_req_received_yr' => 'foia:ProcessingStatisticsReceivedQuantity',
      'field_req_processed_yr' => 'foia:ProcessingStatisticsProcessedQuantity',
      'field_req_pend_end_yr' => 'foia:ProcessingStatisticsPendingAtEndQuantity',
    ];
    $overall_map = [
      'field_overall_req_pend_start_yr' => 'foia:ProcessingStatisticsPendingAtStartQuantity',
      'field_overall_req_received_yr' => 'foia:ProcessingStatisticsReceivedQuantity',
      'field_overall_req_processed_yr' => 'foia:ProcessingStatisticsProcessedQuantity',
      'field_overall_req_pend_end_yr' => 'foia:ProcessingStatisticsPendingAtEndQuantity',
    ];

    $section = $this->addElementNs('foia:ProcessedRequestSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:ProcessingStatistics', 'PS', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:ProcessingStatisticsOrganizationAssociation', 'PS');

    // Add footnote.
    $footnote = trim(strip_tags($this->node->field_footnotes_va->value));
    if ($footnote) {
      $this->addElementNs('foia:FootnoteText', $section, $footnote);
    }
  }

  /**
   * Request Disposition Section.
   *
   * This corresponds to Section V.B(1) of the annual report.
   */
  protected function requestDispositionSection() {
    $component_data = $this->node->field_foia_requests_vb1->referencedEntities();
    $map = [
      'field_full_grants' => 'foia:foia:RequestDispositionFullGrantQuantity',
      'field_part_grants_denials' => 'foia:RequestDispositionPartialGrantQuantity',
      'field_full_denials_ex' => 'foia:RequestDispositionFullExemptionDenialQuantity',
      'field_total' => 'foia:RequestDispositionTotalQuantity',
    ];
    $overall_map = [
      'field_overall_vb1_full_grants' => 'foia:RequestDispositionFullGrantQuantity',
      'field_overall_vb1_part_grants_de' => 'foia:RequestDispositionPartialGrantQuantity',
      'field_overall_vb1_full_denials_e' => 'foia:RequestDispositionFullExemptionDenialQuantity',
      'field_overall_vb1_total' => 'foia:RequestDispositionTotalQuantity',
    ];
    $reason_map = [
      'field_no_rec' => 'NoRecords',
      'field_rec_ref_to_an_comp' => 'Referred',
      'field_req_withdrawn' => 'Withdrawn',
      'field_fee_related_reason' => 'FeeRelated',
      'field_rec_not_desc' => 'NotDescribed',
      'field_imp_req_oth_reason' => 'ImproperRequest',
      'field_not_agency_record' => 'NotAgency',
      'field_dup_request' => 'Duplicate',
      'field_oth' => 'Other',
    ];
    $overall_reason_map = [
      'field_overall_vb1_no_rec' => 'NoRecords',
      'field_overall_vb1_rec_ref_to_an_' => 'Referred',
      'field_overall_vb1_req_withdrawn' => 'Withdrawn',
      'field_overall_vb1_fee_related_re' => 'FeeRelated',
      'field_overall_vb1_rec_not_desc' => 'NotDescribed',
      'field_overall_vb1_imp_req_oth_re' => 'ImproperRequest',
      'field_overall_vb1_not_agency_rec' => 'NotAgency',
      'field_overall_vb1_dup_request' => 'Duplicate',
      'field_overall_vb1_oth' => 'Other',
    ];

    $section = $this->addElementNs('foia:RequestDispositionSection', $this->root);

    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:RequestDisposition', $section);
      $item->setAttribute('s:id', 'RD' . ($delta + 1));
      foreach ($map as $field => $tag) {
        $this->addElementNs($tag, $item, $component->get($field)->value);
      }
      // Add quantity for each denial reason.
      foreach ($reason_map as $field => $reason) {
        if (empty($component->get($field)->value)) {
          continue;
        }
        $subitem = $this->addElementNs('foia:NonExemptionDenial', $item);
        $this->addElementNs('foia:NonExemptionDenialReasonCode', $subitem, $reason);
        $this->addElementNs('foia:NonExemptionDenialQuantity', $subitem, $component->get($field)->value);
      }
    }

    // Add overall data.
    $item = $this->addElementNs('foia:RequestDisposition', $section);
    $item->setAttribute('s:id', 'RD' . 0);
    foreach ($overall_map as $field => $tag) {
      $this->addElementNs($tag, $item, $this->node->get($field)->value);
    }
    // Add quantity for each denial reason.
    foreach ($overall_reason_map as $field => $reason) {
      if (empty($this->node->get($field)->value)) {
        continue;
      }
      $subitem = $this->addElementNs('foia:NonExemptionDenial', $item);
      $this->addElementNs('foia:NonExemptionDenialReasonCode', $subitem, $reason);
      $this->addElementNs('foia:NonExemptionDenialQuantity', $subitem, $this->node->get($field)->value);
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:RequestDispositionOrganizationAssociation', 'RD');

    // Add footnote.
    $footnote = trim(strip_tags($this->node->field_footnotes_vb1->value));
    if ($footnote) {
      $this->addElementNs('foia:FootnoteText', $section, $footnote);
    }
  }

  /**
   * Request Denial Other Reason Section.
   *
   * This corresponds to Section V.B(2) of the annual report.
   */
  protected function requestDenialOtherReasonSection() {
    // @todo
  }

  /**
   * Request Disposition Applied Exemptions Section.
   *
   * This corresponds to Section V.B(3) of the annual report.
   */
  protected function requestDispositionAppliedExemptionsSection() {
    $component_data = $this->node->field_foia_requests_vb3->referencedEntities();
    $exemption_map = [
      'field_ex_1' => 'Ex. 1',
      'field_ex_2' => 'Ex. 2',
      'field_ex_3' => 'Ex. 3',
      'field_ex_4' => 'Ex. 4',
      'field_ex_5' => 'Ex. 5',
      'field_ex_6' => 'Ex. 6',
      'field_ex_7_a' => 'Ex. 7(A)',
      'field_ex_7_b' => 'Ex. 7(B)',
      'field_ex_7_c' => 'Ex. 7(C)',
      'field_ex_7_d' => 'Ex. 7(D)',
      'field_ex_7_e' => 'Ex. 7(E)',
      'field_ex_7_f' => 'Ex. 7(F)',
      'field_ex_8' => 'Ex. 8',
      'field_ex_9' => 'Ex. 9',
    ];
    $overall_exemption_map = [
      'field_overall_vb3_ex_1' => 'Ex. 1',
      'field_overall_vb3_ex_2' => 'Ex. 2',
      'field_overall_vb3_ex_3' => 'Ex. 3',
      'field_overall_vb3_ex_4' => 'Ex. 4',
      'field_overall_vb3_ex_5' => 'Ex. 5',
      'field_overall_vb3_ex_6' => 'Ex. 6',
      'field_overall_vb3_ex_7_a' => 'Ex. 7(A)',
      'field_overall_vb3_ex_7_b' => 'Ex. 7(B)',
      'field_overall_vb3_ex_7_c' => 'Ex. 7(C)',
      'field_overall_vb3_ex_7_d' => 'Ex. 7(D)',
      'field_overall_vb3_ex_7_e' => 'Ex. 7(E)',
      'field_overall_vb3_ex_7_f' => 'Ex. 7(F)',
      'field_overall_vb3_ex_8' => 'Ex. 8',
      'field_overall_vb3_ex_9' => 'Ex. 9',
    ];

    $section = $this->addElementNs('foia:RequestDispositionAppliedExemptionsSection', $this->root);

    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentAppliedExemptions', $section);
      $item->setAttribute('s:id', 'RDE' . ($delta + 1));
      // Add quantity for each exemption code.
      foreach ($exemption_map as $field => $exemption) {
        if (empty($component->get($field)->value)) {
          continue;
        }
        $subitem = $this->addElementNs('foia:AppliedExemption', $item);
        $this->addElementNs('foia:AppliedExemptionCode', $subitem, $exemption);
        $this->addElementNs('foia:AppliedExemptionQuantity', $subitem, $component->get($field)->value);
      }
    }

    // Add overall data.
    $item = $this->addElementNs('foia:ComponentAppliedExemptions', $section);
    $item->setAttribute('s:id', 'RDE' . 0);
    // Add quantity for each exemption code.
    foreach ($overall_exemption_map as $field => $exemption) {
      if (empty($this->node->get($field)->value)) {
        continue;
      }
      $subitem = $this->addElementNs('foia:AppliedExemption', $item);
      $this->addElementNs('foia:AppliedExemptionCode', $subitem, $exemption);
      $this->addElementNs('foia:AppliedExemptionQuantity', $subitem, $this->node->get($field)->value);
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:ComponentAppliedExemptionsOrganizationAssociation', 'RDE');

    // Add footnote.
    $footnote = trim(strip_tags($this->node->field_footnotes_vb3->value));
    if ($footnote) {
      $this->addElementNs('foia:FootnoteText', $section, $footnote);
    }
  }

  /**
   * Processed Appeal Section.
   *
   * This corresponds to Section VI.A of the annual report.
   */
  protected function processedAppealSection() {
    $component_data = $this->node->field_admin_app_via->referencedEntities();
    $map = [
      'field_app_pend_start_yr' => 'foia:ProcessingStatisticsPendingAtStartQuantity',
      'field_app_received_yr' => 'foia:ProcessingStatisticsReceivedQuantity',
      'field_app_processed_yr' => 'foia:ProcessingStatisticsProcessedQuantity',
      'field_app_pend_end_yr' => 'foia:ProcessingStatisticsPendingAtEndQuantity',
    ];
    $overall_map = [
      'field_overall_via_app_pend_start' => 'foia:ProcessingStatisticsPendingAtStartQuantity',
      'field_overall_via_app_recd_yr' => 'foia:ProcessingStatisticsReceivedQuantity',
      'field_overall_via_app_proc_yr' => 'foia:ProcessingStatisticsProcessedQuantity',
      'field_overall_via_app_pend_endyr' => 'foia:ProcessingStatisticsPendingAtEndQuantity',
    ];

    $section = $this->addElementNs('foia:ProcessedAppealSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:ProcessingStatistics', 'PA', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:ProcessingStatisticsOrganizationAssociation', 'PA');

    // Add footnote.
    $footnote = trim(strip_tags($this->node->field_footnotes_via->value));
    if ($footnote) {
      $this->addElementNs('foia:FootnoteText', $section, $footnote);
    }
  }

  /**
   * Appeal Disposition Section.
   *
   * This corresponds to Section VI.B of the annual report.
   */
  protected function appealDispositionSection() {
    $component_data = $this->node->field_admin_app_vib->referencedEntities();
    $map = [
      'field_affirmed_on_app' => 'foia:AppealDispositionAffirmedQuantity',
      'field_part_on_app' => 'foia:AppealDispositionPartialQuantity',
      'field_complete_on_app' => 'foia:AppealDispositionReversedQuantity',
      'field_closed_oth_app' => 'foia:AppealDispositionOtherQuantity',
      'field_total' => 'foia:AppealDispositionTotalQuantity',
    ];
    $overall_map = [
      'field_overall_vib_affirm_on_app' => 'foia:AppealDispositionAffirmedQuantity',
      'field_overall_vib_part_on_app' => 'foia:AppealDispositionPartialQuantity',
      'field_overall_vib_comp_on_app' => 'foia:AppealDispositionReversedQuantity',
      'field_overall_vib_closed_oth_app' => 'foia:AppealDispositionOtherQuantity',
      'field_overall_vib_total' => 'foia:AppealDispositionTotalQuantity',
    ];

    $section = $this->addElementNs('foia:AppealDispositionSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:AppealDisposition', 'AD', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:AppealDispositionOrganizationAssociation', 'AD');

    // Add footnote.
    $footnote = trim(strip_tags($this->node->field_footnotes_vib->value));
    if ($footnote) {
      $this->addElementNs('foia:FootnoteText', $section, $footnote);
    }
  }

  /**
   * Appeal Disposition Applied Exemptions Section.
   *
   * This corresponds to Section VI.C(1) of the annual report.
   */
  protected function appealDispositionAppliedExemptionsSection() {
    // @todo
  }

  /**
   * Appeal Non Exemption Denial Section.
   *
   * This corresponds to Section VI.C(2) of the annual report.
   */
  protected function appealNonExemptionDenialSection() {
    // @todo
  }

  /**
   * Appeal Denial Other Reason Section.
   *
   * This corresponds to Section VI.C(3) of the annual report.
   */
  protected function appealDenialOtherReasonSection() {
    // @todo
  }

  /**
   * Appeal Response Time Section.
   *
   * This corresponds to Section VI.C(4) of the annual report.
   */
  protected function appealResponseTimeSection() {
    // @todo
  }

  /**
   * Oldest Pending Appeal Section.
   *
   * This corresponds to Section VI.C(5) of the annual report.
   */
  protected function oldestPendingAppealSection() {
    // @todo
  }

  /**
   * Processed Response Time Section.
   *
   * This corresponds to Section VII.A of the annual report.
   */
  protected function processedResponseTimeSection() {
    // @todo
  }

  /**
   * Information Granted Response Time Section.
   *
   * This corresponds to Section VII.B of the annual report.
   */
  protected function informationGrantedResponseTimeSection() {
    // @todo
  }

  /**
   * Simple Response Time Increments Section.
   *
   * This corresponds to Section VII.C(1) of the annual report.
   */
  protected function simpleResponseTimeIncrementsSection() {
    // @todo
  }

  /**
   * Complex Response Time Increments Section.
   *
   * This corresponds to Section VII.C(2) of the annual report.
   */
  protected function complexResponseTimeIncrementsSection() {
    // @todo
  }

  /**
   * Expedited Response Time Increments Section.
   *
   * This corresponds to Section VII.C(3) of the annual report.
   */
  protected function expeditedResponseTimeIncrementsSection() {
    // @todo
  }

  /**
   * Pending Perfected Requests Section.
   *
   * This corresponds to Section VII.D of the annual report.
   */
  protected function pendingPerfectedRequestsSection() {
    // @todo
  }

  /**
   * Oldest Pending Request Section.
   *
   * This corresponds to Section VII.E of the annual report.
   */
  protected function oldestPendingRequestSection() {
    // @todo
  }

  /**
   * Expedited Processing Section.
   *
   * This corresponds to Section VIII.A of the annual report.
   */
  protected function expeditedProcessingSection() {
    // @todo
  }

  /**
   * Fee Waiver Section.
   *
   * This corresponds to Section VIII.B of the annual report.
   */
  protected function feeWaiverSection() {
    // @todo
  }

  /**
   * Personnel And Cost Section.
   *
   * This corresponds to Section IX of the annual report.
   */
  protected function personnelAndCostSection() {
    // @todo
  }

  /**
   * Fees Collected Section.
   *
   * This corresponds to Section X of the annual report.
   */
  protected function feesCollectedSection() {
    // @todo
  }

  /**
   * Subsection Used Section.
   *
   * This corresponds to Section XI.A of the annual report.
   */
  protected function subsectionUsedSection() {
    // @todo
  }

  /**
   * Subsection Post Section.
   *
   * This corresponds to Section XI.B of the annual report.
   */
  protected function subsectionPostSection() {
    // @todo
  }

  /**
   * Backlog Section.
   *
   * This corresponds to Section XII.A of the annual report.
   */
  protected function backlogSection() {
    // @todo
  }

  /**
   * Processed Consultation Section.
   *
   * This corresponds to Section XII.B of the annual report.
   */
  protected function processedConsultationSection() {
    // @todo
  }

  /**
   * Oldest Pending Consultation Section.
   *
   * This corresponds to Section XII.C of the annual report.
   */
  protected function oldestPendingConsultationSection() {
    // @todo
  }

  /**
   * Processed Request Comparison Section.
   *
   * This corresponds to Section XII.D(1) of the annual report.
   */
  protected function processedRequestComparisonSection() {
    // @todo
  }

  /**
   * Backlogged Request Comparison Section.
   *
   * This corresponds to Section XII.D(2) of the annual report.
   */
  protected function backloggedRequestComparisonSection() {
    // @todo
  }

  /**
   * Processed Appeal Comparison Section.
   *
   * This corresponds to Section XII.E(1) of the annual report.
   */
  protected function processedAppealComparisonSection() {
    // @todo
  }

  /**
   * Backlogged Appeal Comparison Section.
   *
   * This corresponds to Section XII.E(2) of the annual report.
   */
  protected function backloggedAppealComparisonSection() {
    // @todo
  }

}
