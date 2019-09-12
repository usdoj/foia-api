<?php

namespace Drupal\foia_export_xml;

use Drupal\paragraphs\Entity\Paragraph;
use DOMDocument;
use DOMElement;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;
use Exception;

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
    $this->document = new DOMDocument('1.0');
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
   *   Parent tags of a element.
   * @param string $value
   *   (optional) The text value of the new element.
   *
   * @return \DOMElement
   *   The newly added element.
   *
   * @throws \Exception
   */
  protected function addElementNs($tag, DOMElement $parent, $value = NULL) {
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
      throw new Exception("Unrecognized prefix: $prefix");
    }
    $element = $this->document->createElementNS($namespaces[$prefix], $local_name, $value);
    $parent->appendChild($element);
    return $element;
  }

  /**
   * Add data from several fields on an entity, each with a corresponding label.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity, such as a node or a paragraph.
   * @param \DOMElement $parent
   *   The parent element to which new nodes will be added.
   * @param string $tag
   *   The XML tag of the element to be added.
   * @param string $label_tag
   *   The XML tag of the label element.
   * @param string $quantity_tag
   *   The XML tag of the quantity element.
   * @param string[] $map
   *   An array mapping some fields on $entity to labels.
   */
  protected function addLabeledQuantity(EntityInterface $entity, DOMElement $parent, $tag, $label_tag, $quantity_tag, array $map) {
    foreach ($map as $field => $label) {
      if (empty($entity->get($field)->value)) {
        continue;
      }
      $item = $this->addElementNs($tag, $parent);
      $this->addElementNs($label_tag, $item, $label);
      $this->addElementNs($quantity_tag, $item, $entity->get($field)->value);
    }
  }

  /**
   * Add component data.
   *
   * Add data from an array of paragraphs with per-component data and
   * corresponding overall data from the node.
   *
   * @param Drupal\Core\Entity\EntityInterface[] $component_data
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
  protected function addComponentData(array $component_data, DOMElement $parent, $data_tag, $prefix, array $map, array $overall_map) {
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
   * @param Drupal\Core\Entity\EntityInterface[] $component_data
   *   An array of paragraphs with per-component data, each with
   *   field_agency_component referencing an Agency Component node.
   * @param \DOMElement $parent
   *   The parent element to which new nodes will be added.
   * @param string $tag
   *   The XML tag of the association section.
   * @param string $prefix
   *   The base string used in the s:ref attribute.
   *
   * @throws \Exception
   */
  protected function addProcessingAssociations(array $component_data, DOMElement $parent, $tag, $prefix) {
    // Add processing association for each component.
    foreach ($component_data as $delta => $component) {
      $agency_component = $component->field_agency_component->referencedEntities()[0];
      $matchup = $this->addElementNs($tag, $parent);
      $this
        ->addElementNs('foia:ComponentDataReference', $matchup)
        ->setAttribute('s:ref', $prefix . ($delta + 1));
      if ($agency_component) {
        $this
          ->addElementNs('nc:OrganizationReference', $matchup)
          ->setAttribute('s:ref', $this->componentMap[$agency_component->id()]);
      }
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
    $statute = $this->node->field_statute_iv->referencedEntities();
    $statuteSection = $this->addElementNs('foia:Exemption3StatuteSection', $this->root);

    foreach ($statute as $delta => $component) {
      $local_id = 'ES' . ($delta + 1);

      $suborg = $this->addElementNs('foia:ReliedUponStatute', $statuteSection);
      $suborg->setAttribute('s:id', $local_id);
      $item = $this->addElementNs('j:StatuteDescriptionText', $suborg, $component->field_statute->value);
      $info_withheld = SafeMarkup::checkPlain($component->field_type_of_info_withheld->value);
      $item = $this->addElementNs('foia:ReliedUponStatuteInformationWithheldText', $suborg, $info_withheld);
      $itemCase = $this->addElementNs('nc:Case', $suborg);
      $itemCaseItem = $this->addElementNs('nc:CaseTitleText', $itemCase, $component->field_case_citation->value);
    }

    // Add footnote.
    if ($this->node->field_footnotes_iv->value) {
      foreach ($this->node->field_footnotes_iv as $footnote) {
        $footnote = SafeMarkup::checkPlain($footnote->value);
        if ($footnote) {
          $this->addElementNs('foia:FootnoteText', $statuteSection, $footnote);
        }
      }
    }
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
      $this->addLabeledQuantity($component, $item, 'foia:NonExemptionDenial', 'foia:NonExemptionDenialReasonCode', 'foia:NonExemptionDenialQuantity', $reason_map);
    }

    // Add overall data.
    $item = $this->addElementNs('foia:RequestDisposition', $section);
    $item->setAttribute('s:id', 'RD' . 0);
    foreach ($overall_map as $field => $tag) {
      $this->addElementNs($tag, $item, $this->node->get($field)->value);
    }
    // Add quantity for each denial reason.
    $this->addLabeledQuantity($this->node, $item, 'foia:NonExemptionDenial', 'foia:NonExemptionDenialReasonCode', 'foia:NonExemptionDenialQuantity', $overall_reason_map);

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
    $component_data = $this->node->field_foia_requests_vb2->referencedEntities();
    $section = $this->addElementNs('foia:RequestDenialOtherReasonSection', $this->root);
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentOtherDenialReason', $section);
      $item->setAttribute('s:id', 'CODR' . ($delta + 1));
      $field_foia_req_vb2_info = $component->get('field_foia_req_vb2_info')->getValue();
      if (!empty($field_foia_req_vb2_info)) {
        foreach ($field_foia_req_vb2_info as $field_value) {
          $item12 = $this->addElementNs('foia:OtherDenialReason', $item);
          $target_id = $field_value['target_id'];
          $p = Paragraph::load($target_id);
          $this->addElementNs('foia:OtherDenialReasonDescriptionText', $item12, $p->get('field_desc_oth_reasons')->value);
          $this->addElementNs('foia:OtherDenialReasonQuantity', $item12, $p->get('field_num_relied_upon')->value);
        }
      }
      $this->addElementNs('foia:ComponentOtherDenialReasonQuantity', $item, $component->get('field_total')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:OtherDenialReasonOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'CODR' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
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
      $this->addLabeledQuantity($component, $item, 'foia:AppliedExemption', 'foia:AppliedExemptionCode', 'foia:AppliedExemptionQuantity', $exemption_map);
    }

    // Add overall data.
    $item = $this->addElementNs('foia:ComponentAppliedExemptions', $section);
    $item->setAttribute('s:id', 'RDE' . 0);
    // Add quantity for each exemption code.
    $this->addLabeledQuantity($this->node, $item, 'foia:AppliedExemption', 'foia:AppliedExemptionCode', 'foia:AppliedExemptionQuantity', $overall_exemption_map);

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
    $component_data = $this->node->field_admin_app_vic1->referencedEntities();
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
      'field_overall_vic1_ex_1' => 'Ex. 1',
      'field_overall_vic1_ex_2' => 'Ex. 2',
      'field_overall_vic1_ex_3' => 'Ex. 3',
      'field_overall_vic1_ex_4' => 'Ex. 4',
      'field_overall_vic1_ex_5' => 'Ex. 5',
      'field_overall_vic1_ex_6' => 'Ex. 6',
      'field_overall_vic1_ex_7_a' => 'Ex. 7(A)',
      'field_overall_vic1_ex_7_b' => 'Ex. 7(B)',
      'field_overall_vic1_ex_7_c' => 'Ex. 7(C)',
      'field_overall_vic1_ex_7_d' => 'Ex. 7(D)',
      'field_overall_vic1_ex_7_e' => 'Ex. 7(E)',
      'field_overall_vic1_ex_7_f' => 'Ex. 7(F)',
      'field_overall_vic1_ex_8' => 'Ex. 8',
      'field_overall_vic1_ex_9' => 'Ex. 9',
    ];

    $section = $this->addElementNs('foia:AppealDispositionAppliedExemptionsSection', $this->root);

    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentAppliedExemptions', $section);
      $item->setAttribute('s:id', 'ADE' . ($delta + 1));
      // Add quantity for each exemption code.
      $this->addLabeledQuantity($component, $item, 'foia:AppliedExemption', 'foia:AppliedExemptionCode', 'foia:AppliedExemptionQuantity', $exemption_map);
    }

    // Add overall data.
    $item = $this->addElementNs('foia:ComponentAppliedExemptions', $section);
    $item->setAttribute('s:id', 'ADE' . 0);
    // Add quantity for each exemption code.
    $this->addLabeledQuantity($this->node, $item, 'foia:AppliedExemption', 'foia:AppliedExemptionCode', 'foia:AppliedExemptionQuantity', $overall_exemption_map);

    $this->addProcessingAssociations($component_data, $section, 'foia:ComponentAppliedExemptionsOrganizationAssociation', 'ADE');

    // Add footnote.
    $footnote = trim(strip_tags($this->node->field_footnotes_vic1->value));
    if ($footnote) {
      $this->addElementNs('foia:FootnoteText', $section, $footnote);
    }
  }

  /**
   * Appeal Non Exemption Denial Section.
   *
   * This corresponds to Section VI.C(2) of the annual report.
   */
  protected function appealNonExemptionDenialSection() {
    $component_data = $this->node->field_admin_app_vic2->referencedEntities();
    $reason_map = [
      'field_no_rec' => 'NoRecords',
      'field_rec_refer_initial' => 'Referred',
      'field_req_withdrawn' => 'Withdrawn',
      'field_fee_related_reason' => 'FeeRelated',
      'field_rec_not_desc' => 'NotDescribed',
      'field_imp_req_oth_reason' => 'ImproperRequest',
      'field_not_agency_record' => 'NotAgency',
      'field_dup_req' => 'Duplicate',
      'field_req_in_lit' => 'InLitigation',
      'field_app_denial_exp' => 'ExpeditedDenial',
      'field_oth' => 'Other',
    ];
    $overall_reason_map = [
      'field_overall_vic2_no_rec' => 'NoRecords',
      'field_overall_vic2_rec_refer_ini' => 'Referred',
      'field_overall_vic2_req_withdrawn' => 'Withdrawn',
      'field_overall_vic2_fee_rel_reas' => 'FeeRelated',
      'field_overall_vic2_rec_not_desc' => 'NotDescribed',
      'field_overall_vic2_imp_req_oth' => 'ImproperRequest',
      'field_overall_vic2_not_agency_re' => 'NotAgency',
      'field_overall_vic2_dup_req' => 'Duplicate',
      'field_overall_vic2_req_in_lit' => 'InLitigation',
      'field_overall_vic2_app_denial_ex' => 'ExpeditedDenial',
      'field_overall_vic2_oth' => 'Other',
    ];

    $section = $this->addElementNs('foia:AppealNonExemptionDenialSection', $this->root);

    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:AppealNonExemptionDenial', $section);
      $item->setAttribute('s:id', 'ANE' . ($delta + 1));
      $this->addLabeledQuantity($component, $item, 'foia:NonExemptionDenial', 'foia:NonExemptionDenialReasonCode', 'foia:NonExemptionDenialQuantity', $reason_map);
    }

    // Add overall data.
    $item = $this->addElementNs('foia:AppealNonExemptionDenial', $section);
    $item->setAttribute('s:id', 'ANE' . 0);
    $this->addLabeledQuantity($this->node, $item, 'foia:NonExemptionDenial', 'foia:NonExemptionDenialReasonCode', 'foia:NonExemptionDenialQuantity', $overall_reason_map);

    $this->addProcessingAssociations($component_data, $section, 'foia:AppealNonExemptionDenialOrganizationAssociation', 'ANE');

    // Add footnote.
    $footnote = trim(strip_tags($this->node->field_footnotes_vic2->value));
    if ($footnote) {
      $this->addElementNs('foia:FootnoteText', $section, $footnote);
    }
  }

  /**
   * Appeal Denial Other Reason Section.
   *
   * This corresponds to Section VI.C(3) of the annual report.
   */
  protected function appealDenialOtherReasonSection() {
    $vic3 = $this->node->field_admin_app_vic3->referencedEntities();

    $section = $this->addElementNs('foia:AppealDenialOtherReasonSection', $this->root);
    if ($vic3) {
      foreach ($vic3 as $delta => $vic3_field) {
        $sec_item = $this->addElementNs('foia:ComponentOtherDenialReason', $section);
        $sec_item->setAttribute('s:id', 'ADOR' . ($delta + 1));
        $item = $this->addElementNs('foia:OtherDenialReason', $sec_item);
        $item_value = $this->addElementNs('foia:OtherDenialReasonDescriptionText', $item, "nested paragraph field date");
        $item1_value = $this->addElementNs('foia:OtherDenialReasonQuantity', $item, "Nested paragraph data");
      }
    }

    $sec2 = $this->addElementNs('foia:OtherDenialReasonOrganizationAssociation', $section);
    $sec2_item = $this->addElementNs('foia:ComponentDataReference', $sec2);
    $sec2_item->setAttribute('s:ref', 'ADOR8');
    $sec2_item1 = $this->addElementNs('nc:OrganizationReference', $sec2);
    $sec2_item1->setAttribute('s:ref', 'ORG2');
    // Add footnote.
    $footnote = SafeMarkup::checkPlain($this->node->field_footnotes_vic3->value);
    if ($footnote) {
      $this->addElementNs('foia:FootnoteText', $section, $footnote);
    }
  }

  /**
   * Appeal Response Time Section.
   *
   * This corresponds to Section VI.C(4) of the annual report.
   */
  protected function appealResponseTimeSection() {
    $component_data = $this->node->field_admin_app_vic4->referencedEntities();
    $map = [
      'field_med_num_days' => 'foia:ResponseTimeMedianDaysValue',
      'field_avg_num_days' => 'foia:ResponseTimeAverageDaysValue',
      'field_low_num_days' => 'foia:ResponseTimeLowestDaysValue',
      'field_high_num_days' => 'foia:ResponseTimeHighestDaysValue',
    ];
    $overall_map = [
      'field_overall_vic4_med_num_days' => 'foia:ResponseTimeMedianDaysValue',
      'field_overall_vic4_avg_num_days' => 'foia:ResponseTimeAverageDaysValue',
      'field_overall_vic4_low_num_days' => 'foia:ResponseTimeLowestDaysValue',
      'field_overall_vic4_high_num_days' => 'foia:ResponseTimeHighestDaysValue',
    ];

    $section = $this->addElementNs('foia:AppealResponseTimeSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:ResponseTime', 'ART', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:ResponseTimeOrganizationAssociation', 'ART');

    // Add footnote.
    $footnote = trim(strip_tags($this->node->field_footnotes_vic4->value));
    if ($footnote) {
      $this->addElementNs('foia:FootnoteText', $section, $footnote);
    }
  }

  /**
   * Oldest Pending Appeal Section.
   *
   * This corresponds to Section VI.C(5) of the annual report.
   */
  protected function oldestPendingAppealSection() {
    $component_data = $this->node->field_admin_app_vic5->referencedEntities();
    $section = $this->addElementNs('foia:OldestPendingAppealSection', $this->root);
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:OldestPendingItems', $section);
      $item->setAttribute('s:id', 'OPA' . ($delta + 1));
      for ($i = 1; $i <= 10; $i++) {
        $item2 = $this->addElementNs('foia:OldItem', $item);
        $this->addElementNs('foia:OldItemReceiptDate', $item2, $component->get('field_date_' . $i)->value);
        $this->addElementNs('foia:OldItemPendingDaysQuantity', $item2, $component->get('field_num_days_' . $i)->value);
      }
    }
    foreach ($component_data as $delta => $component) {
      $item11 = $this->addElementNs('foia:OldestPendingItemsOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item11);
      $item21->setAttribute('s:id', 'OPA' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item11);
      $item22->setAttribute('s:id', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Processed Response Time Section.
   *
   * This corresponds to Section VII.A of the annual report.
   */
  protected function processedResponseTimeSection() {
    $component_data = $this->node->field_proc_req_viia->referencedEntities();
    $section = $this->addElementNs('foia:ProcessedResponseTimeSection', $this->root);
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item1 = $this->addElementNs('foia:ProcessedResponseTime', $section);
      $item1->setAttribute('s:id', 'PRT' . ($delta + 1));
      $item11 = $this->addElementNs('foia:SimpleResponseTime', $item1);
      $this->addElementNs('foia:ResponseTimeMedianDaysValue', $item11, $component->get('field_sim_med')->value);
      $this->addElementNs('foia:ResponseTimeAverageDaysValue', $item11, $component->get('field_sim_avg')->value);
      $this->addElementNs('foia:ResponseTimeLowestDaysValue', $item11, $component->get('field_sim_low')->value);
      $this->addElementNs('foia:ResponseTimeHighestDaysValue', $item11, $component->get('field_sim_high')->value);

      $item12 = $this->addElementNs('foia:ComplexResponseTime', $item1);
      $this->addElementNs('foia:ResponseTimeMedianDaysValue', $item12, $component->get('field_comp_med')->value);
      $this->addElementNs('foia:ResponseTimeAverageDaysValue', $item12, $component->get('field_comp_avg')->value);
      $this->addElementNs('foia:ResponseTimeLowestDaysValue', $item12, $component->get('field_comp_low')->value);
      $this->addElementNs('foia:ResponseTimeHighestDaysValue', $item12, $component->get('field_comp_high')->value);

      $item13 = $this->addElementNs('foia:ExpeditedResponseTime', $item1);
      $this->addElementNs('foia:ResponseTimeMedianDaysValue', $item13, $component->get('field_exp_med')->value);
      $this->addElementNs('foia:ResponseTimeAverageDaysValue', $item13, $component->get('field_exp_avg')->value);
      $this->addElementNs('foia:ResponseTimeLowestDaysValue', $item13, $component->get('field_exp_low')->value);
      $this->addElementNs('foia:ResponseTimeHighestDaysValue', $item13, $component->get('field_exp_high')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:ProcessedResponseTimeOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'PRT' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Information Granted Response Time Section.
   *
   * This corresponds to Section VII.B of the annual report.
   */
  protected function informationGrantedResponseTimeSection() {
    $component_data = $this->node->field_proc_req_viib->referencedEntities();
    $section = $this->addElementNs('foia:InformationGrantedResponseTimeSection', $this->root);
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item1 = $this->addElementNs('foia:ProcessedResponseTime', $section);
      $item1->setAttribute('s:id', 'IGR' . ($delta + 1));
      $item11 = $this->addElementNs('foia:SimpleResponseTime', $item1);
      $this->addElementNs('foia:ResponseTimeMedianDaysValue', $item11, $component->get('field_sim_med')->value);
      $this->addElementNs('foia:ResponseTimeAverageDaysValue', $item11, $component->get('field_sim_avg')->value);
      $this->addElementNs('foia:ResponseTimeLowestDaysValue', $item11, $component->get('field_sim_low')->value);
      $this->addElementNs('foia:ResponseTimeHighestDaysValue', $item11, $component->get('field_sim_high')->value);

      $item12 = $this->addElementNs('foia:ComplexResponseTime', $item1);
      $this->addElementNs('foia:ResponseTimeMedianDaysValue', $item12, $component->get('field_comp_med')->value);
      $this->addElementNs('foia:ResponseTimeAverageDaysValue', $item12, $component->get('field_comp_avg')->value);
      $this->addElementNs('foia:ResponseTimeLowestDaysValue', $item12, $component->get('field_comp_low')->value);
      $this->addElementNs('foia:ResponseTimeHighestDaysValue', $item12, $component->get('field_comp_high')->value);

      $item13 = $this->addElementNs('foia:ExpeditedResponseTime', $item1);
      $this->addElementNs('foia:ResponseTimeMedianDaysValue', $item13, $component->get('field_exp_med')->value);
      $this->addElementNs('foia:ResponseTimeAverageDaysValue', $item13, $component->get('field_exp_avg')->value);
      $this->addElementNs('foia:ResponseTimeLowestDaysValue', $item13, $component->get('field_exp_low')->value);
      $this->addElementNs('foia:ResponseTimeHighestDaysValue', $item13, $component->get('field_exp_high')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:ProcessedResponseTimeOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'IGR' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Simple Response Time Increments Section.
   *
   * This corresponds to Section VII.C(1) of the annual report.
   */
  protected function simpleResponseTimeIncrementsSection() {
    $component_data = $this->node->field_proc_req_viic1->referencedEntities();
    $section = $this->addElementNs('foia:SimpleResponseTimeIncrementsSection', $this->root);
    /** @var array $fields */
    $fields = [
      'field_1_20_days' => '1-20',
      'field_21_40_days' => '21-40',
      'field_41_60_days' => '41-60',
      'field_61_80_days' => '61-20',
      'field_81_100_days' => '81-100',
      'field_101_120_days' => '101-120',
      'field_121_140_days' => '121-140',
      'field_141_160_days' => '141-160',
      'field_161_180_days' => '161-180',
      'field_181_200_days' => '181-200',
      'field_201_300_days' => '201-300',
      'field_301_400_days' => '301-400',
      'field_400_up_days' => '400+',
    ];
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentResponseTimeIncrements', $section);
      $item->setAttribute('s:id', 'CRT' . ($delta + 1));
      foreach ($fields as $field => $value) {
        $item_field = $this->addElementNs('foia:TimeIncrement', $item);
        $this->addElementNs('foia:TimeIncrementCode', $item_field, $value);
        $this->addElementNs('foia:TimeIncrementProcessedQuantity', $item_field, $component->get($field)->value);
      }
      $this->addElementNs('foia:TimeIncrementTotalQuantity', $item, $component->get('field_total')->value);
    }
  }

  /**
   * Complex Response Time Increments Section.
   *
   * This corresponds to Section VII.C(2) of the annual report.
   */
  protected function complexResponseTimeIncrementsSection() {
    $component_data = $this->node->field_proc_req_viic2->referencedEntities();
    $section = $this->addElementNs('foia:ComplexResponseTimeIncrementsSection', $this->root);
    /** @var array $fields */
    $fields = [
      'field_1_20_days' => '1-20',
      'field_21_40_days' => '21-40',
      'field_41_60_days' => '41-60',
      'field_61_80_days' => '61-20',
      'field_81_100_days' => '81-100',
      'field_101_120_days' => '101-120',
      'field_121_140_days' => '121-140',
      'field_141_160_days' => '141-160',
      'field_161_180_days' => '161-180',
      'field_181_200_days' => '181-200',
      'field_201_300_days' => '201-300',
      'field_301_400_days' => '301-400',
      'field_400_up_days' => '400+',
    ];
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentResponseTimeIncrements', $section);
      $item->setAttribute('s:id', 'CRT' . ($delta + 1));
      foreach ($fields as $field => $value) {
        $item_field = $this->addElementNs('foia:TimeIncrement', $item);
        $this->addElementNs('foia:TimeIncrementCode', $item_field, $value);
        $this->addElementNs('foia:TimeIncrementProcessedQuantity', $item_field, $component->get($field)->value);
      }
      $this->addElementNs('foia:TimeIncrementTotalQuantity', $item, $component->get('field_total')->value);
    }
  }

  /**
   * Expedited Response Time Increments Section.
   *
   * This corresponds to Section VII.C(3) of the annual report.
   */
  protected function expeditedResponseTimeIncrementsSection() {
    $component_data = $this->node->field_proc_req_viic3->referencedEntities();
    $section = $this->addElementNs('foia:ExpeditedResponseTimeIncrementsSection', $this->root);
    /** @var array $fields */
    $fields = [
      'field_1_20_days' => '1-20',
      'field_21_40_days' => '21-40',
      'field_41_60_days' => '41-60',
      'field_61_80_days' => '61-20',
      'field_81_100_days' => '81-100',
      'field_101_120_days' => '101-120',
      'field_121_140_days' => '121-140',
      'field_141_160_days' => '141-160',
      'field_161_180_days' => '161-180',
      'field_181_200_days' => '181-200',
      'field_201_300_days' => '201-300',
      'field_301_400_days' => '301-400',
      'field_400_up_days' => '400+',
    ];
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentResponseTimeIncrements', $section);
      $item->setAttribute('s:id', 'ERT' . ($delta + 1));
      foreach ($fields as $field => $value) {
        $item_field = $this->addElementNs('foia:TimeIncrement', $item);
        $this->addElementNs('foia:TimeIncrementCode', $item_field, $value);
        $this->addElementNs('foia:TimeIncrementProcessedQuantity', $item_field, $component->get($field)->value);
      }
      $this->addElementNs('foia:TimeIncrementTotalQuantity', $item, $component->get('field_total')->value);
    }
  }

  /**
   * Pending Perfected Requests Section.
   *
   * This corresponds to Section VII.D of the annual report.
   */
  protected function pendingPerfectedRequestsSection() {
    $component_data = $this->node->field_pending_requests_vii_d_->referencedEntities();
    $section = $this->addElementNs('foia:PendingPerfectedRequestsSection', $this->root);
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item1 = $this->addElementNs('foia:PendingPerfectedRequests', $section);
      $item1->setAttribute('s:id', 'PPR' . ($delta + 1));
      $item11 = $this->addElementNs('foia:SimplePendingRequestStatistics', $item1);
      $this->addElementNs('foia:PendingRequestQuantity', $item11, $component->get('field_sim_pend')->value);
      $this->addElementNs('foia:PendingRequestMedianDaysValue', $item11, $component->get('field_sim_med')->value);
      $this->addElementNs('foia:PendingRequestAverageDaysValue', $item11, $component->get('field_sim_avg')->value);

      $item12 = $this->addElementNs('foia:ComplexPendingRequestStatistics', $item1);
      $this->addElementNs('foia:PendingRequestQuantity', $item12, $component->get('field_comp_pend')->value);
      $this->addElementNs('foia:PendingRequestMedianDaysValue', $item12, $component->get('field_comp_med')->value);
      $this->addElementNs('foia:PendingRequestAverageDaysValue', $item12, $component->get('field_comp_avg')->value);

      $item13 = $this->addElementNs('foia:ExpeditedPendingRequestStatistics', $item1);
      $this->addElementNs('foia:PendingRequestQuantity', $item13, $component->get('field_exp_pend')->value);
      $this->addElementNs('foia:PendingRequestMedianDaysValue', $item13, $component->get('field_exp_med')->value);
      $this->addElementNs('foia:PendingRequestAverageDaysValue', $item13, $component->get('field_exp_avg')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:PendingPerfectedRequestsOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'PPR' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Oldest Pending Request Section.
   *
   * This corresponds to Section VII.E of the annual report.
   */
  protected function oldestPendingRequestSection() {
    $component_data = $this->node->field_admin_app_viie->referencedEntities();
    $section = $this->addElementNs('foia:OldestPendingRequestSection', $this->root);
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:OldestPendingItems', $section);
      $item->setAttribute('s:id', 'OPR' . ($delta + 1));
      for ($i = 1; $i <= 10; $i++) {
        $item2 = $this->addElementNs('foia:OldItem', $item);
        $this->addElementNs('foia:OldItemReceiptDate', $item2, $component->get('field_date_' . $i)->value);
        $this->addElementNs('foia:OldItemPendingDaysQuantity', $item2, $component->get('field_num_days_' . $i)->value);
      }
    }
    foreach ($component_data as $delta => $component) {
      $item11 = $this->addElementNs('foia:PendingPerfectedRequestsOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item11);
      $item21->setAttribute('s:id', 'PPR' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item11);
      $item22->setAttribute('s:id', 'ORG' . ($delta + 1));
    }
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
    $component_data = $this->node->field_foia_xiia->referencedEntities();
    $section = $this->addElementNs('foia:BacklogSection', $this->root);
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:Backlog', $section);
      $item->setAttribute('s:id', 'BK' . ($delta + 1));
      $this->addElementNs('foia:BackloggedRequestQuantity', $item, $component->get('field_back_req_end_yr')->value);
      $this->addElementNs('foia:BackloggedAppealQuantity', $item, $component->get('field_back_app_end_yr')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:BacklogOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'BK' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Processed Consultation Section.
   *
   * This corresponds to Section XII.B of the annual report.
   */
  protected function processedConsultationSection() {
    $component_data = $this->node->field_foia_xiib->referencedEntities();
    $section = $this->addElementNs('foia:ProcessedConsultationSection', $this->root);
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ProcessingStatistics', $section);
      $item->setAttribute('s:id', 'PCN' . ($delta + 1));
      $this->addElementNs('foia:ProcessingStatisticsPendingAtStartQuantity', $item, $component->get('field_pend_start_yr')->value);
      $this->addElementNs('foia:ProcessingStatisticsReceivedQuantity', $item, $component->get('field_con_during_yr')->value);
      $this->addElementNs('foia:ProcessingStatisticsProcessedQuantity', $item, $component->get('field_proc_start_yr')->value);
      $this->addElementNs('foia:ProcessingStatisticsPendingAtEndQuantity', $item, $component->get('field_pend_end_yr')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:ProcessingStatisticsOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'PCN' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Oldest Pending Consultation Section.
   *
   * This corresponds to Section XII.C of the annual report.
   */
  protected function oldestPendingConsultationSection() {
    $component_data = $this->node->field_foia_xiic->referencedEntities();
    $section = $this->addElementNs('foia:OldestPendingConsultationSection', $this->root);
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:OldestPendingItems', $section);
      $item->setAttribute('s:id', 'OPA' . ($delta + 1));
      for ($i = 1; $i <= 10; $i++) {
        $item2 = $this->addElementNs('foia:OldItem', $item);
        $this->addElementNs('foia:OldItemReceiptDate', $item2, $component->get('field_date_' . $i)->value);
        $this->addElementNs('foia:OldItemPendingDaysQuantity', $item2, $component->get('field_num_days_' . $i)->value);
      }
    }
    foreach ($component_data as $delta => $component) {
      $item11 = $this->addElementNs('foia:OldestPendingItemsOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item11);
      $item21->setAttribute('s:id', 'OPA' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item11);
      $item22->setAttribute('s:id', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Processed Request Comparison Section.
   *
   * This corresponds to Section XII.D(1) of the annual report.
   */
  protected function processedRequestComparisonSection() {
    $component_data = $this->node->field_foia_xiid1->referencedEntities();
    $section = $this->addElementNs('foia:ProcessedRequestComparisonSection', $this->root);
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ProcessingComparison', $section);
      $item->setAttribute('s:id', 'RPC' . ($delta + 1));
      $this->addElementNs('foia:ItemsReceivedLastYearQuantity', $item, $component->get('field_proc_last_yr')->value);
      $this->addElementNs('foia:ItemsReceivedCurrentYearQuantity', $item, $component->get('field_received_cur_yr')->value);
      $this->addElementNs('foia:ItemsProcessedLastYearQuantity', $item, $component->get('field_proc_last_yr')->value);
      $this->addElementNs('foia:ItemsProcessedCurrentYearQuantity', $item, $component->get('field_proc_cur_yr')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:ProcessingComparisonOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'RPC' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Backlogged Request Comparison Section.
   *
   * This corresponds to Section XII.D(2) of the annual report.
   */
  protected function backloggedRequestComparisonSection() {
    $component_data = $this->node->field_foia_xiid2->referencedEntities();
    $section = $this->addElementNs('foia:BackloggedRequestComparisonSection', $this->root);
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:BacklogComparison', $section);
      $item->setAttribute('s:id', 'RBC' . ($delta + 1));
      $this->addElementNs('foia:BacklogLastYearQuantity', $item, $component->get('field_back_prev_yr')->value);
      $this->addElementNs('foia:BacklogCurrentYearQuantity', $item, $component->get('field_back_cur_yr')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:BacklogComparisonOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'RBC' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Processed Appeal Comparison Section.
   *
   * This corresponds to Section XII.E(1) of the annual report.
   */
  protected function processedAppealComparisonSection() {
    $component_data = $this->node->field_foia_xiie1->referencedEntities();
    $section = $this->addElementNs('foia:ProcessedAppealComparisonSection', $this->root);
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ProcessingComparison', $section);
      $item->setAttribute('s:id', 'APC' . ($delta + 1));
      $this->addElementNs('foia:ItemsReceivedLastYearQuantity', $item, $component->get('field_proc_last_yr')->value);
      $this->addElementNs('foia:ItemsReceivedCurrentYearQuantity', $item, $component->get('field_received_cur_yr')->value);
      $this->addElementNs('foia:ItemsProcessedLastYearQuantity', $item, $component->get('field_proc_last_yr')->value);
      $this->addElementNs('foia:ItemsProcessedCurrentYearQuantity', $item, $component->get('field_proc_cur_yr')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:ProcessingComparisonOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'APC' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
  }

  /**
   * Backlogged Appeal Comparison Section.
   *
   * This corresponds to Section XII.E(2) of the annual report.
   */
  protected function backloggedAppealComparisonSection() {
    $component_data = $this->node->field_foia_xiie2->referencedEntities();
    $section = $this->addElementNs('foia:BackloggedAppealComparisonSection', $this->root);
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:BacklogComparison', $section);
      $item->setAttribute('s:id', 'ABC' . ($delta + 1));
      $this->addElementNs('foia:BacklogLastYearQuantity', $item, $component->get('field_back_prev_yr')->value);
      $this->addElementNs('foia:BacklogCurrentYearQuantity', $item, $component->get('field_back_cur_yr')->value);
    }

    foreach ($component_data as $delta => $component) {
      $item2 = $this->addElementNs('foia:BacklogComparisonOrganizationAssociation', $section);
      $item21 = $this->addElementNs('foia:ComponentDataReference', $item2);
      $item21->setAttribute('s:ref', 'ABC' . ($delta + 1));
      $item22 = $this->addElementNs('nc:OrganizationReference', $item2);
      $item22->setAttribute('s:ref', 'ORG' . ($delta + 1));
    }
  }

}
