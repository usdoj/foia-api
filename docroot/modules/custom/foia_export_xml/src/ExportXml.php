<?php

namespace Drupal\foia_export_xml;

use Drupal\Core\Entity\EntityInterface;
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
  protected function addLabeledQuantity(EntityInterface $entity, \DOMElement $parent, $tag, $label_tag, $quantity_tag, array $map) {
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
   * @param Drupal\Core\Entity\EntityInterface[] $component_data
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
        if($agency_component){
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
    // @todo
    //field_statute_iv
    //field_footnotes_iv
   
    $statute = $this->node->field_statute_iv->referencedEntities();    
    $statuteSection = $this->addElementNs('foia:Exemption3StatuteSection', $this->root);

    foreach ($statute as $delta => $component) {
      $local_id = 'ES' . ($delta + 1);

      $suborg = $this->addElementNs('foia:ReliedUponStatute', $statuteSection);
      $suborg->setAttribute('s:id', $local_id);
      $item = $this->addElementNs('j:StatuteDescriptionText', $suborg, $component->field_statute->value);
      $info_withheld = \Drupal\Component\Utility\SafeMarkup::checkPlain($component->field_type_of_info_withheld->value);
      $item = $this->addElementNs('foia:ReliedUponStatuteInformationWithheldText', $suborg, $info_withheld);
      $itemCase = $this->addElementNs('nc:Case', $suborg);
      $itemCaseItem = $this->addElementNs('nc:CaseTitleText', $itemCase, $component->field_case_citation->value);
    }
    
    //field_agency_component_inf
    //Adding foia:ReliedUponStatuteOrganizationAssociation tag
    /** Debuging code should be clean *****************************
    foreach ($statute as $delta => $agency_component) {
      if ($agency_component->field_agency_component_inf){
        $local_id = 'ES' . ($delta + 1);
        $suborg = $this->addElementNs('foia:ReliedUponStatuteOrganizationAssociation', $statuteSection);
      //foia:ComponentDataReference  field_agency_component_inf
        $field_target = $agency_component->get('field_agency_component_inf')->first()->getValue();
        $target_id = $field_target['target_id'];
        //print_r($field_target);
        //$field_target = $agency_component->id();
       // echo $entity_type = $agency_component->getType();
        $agency = \Drupal::entityManager()->getStorage('paragraph')->load($target_id);
        //$agency = $agency_component->get('field_agency_component_inf')->first()->value;
        //print_r($paragraph_field);
        dump($agency);
        //$f =  $agency_component->get('field_agency_component_inf')->first()->getValue();
        //print_r($f);
        $agency2 = $agency->get('field_num_relied_by_agency_comp');
        //echo $agency->id();
        //dump( $agency2);
        echo $agency->field_num_relied_by_agency_comp->value; 
        die();
       // $item1 = $this->addElementNs('foia:ComponentDataReference ', $suborg, 'sdsds');
        //$item1->setAttribute('s:ref', $local_id);
       // $item2 = $this->addElementNs('foia:ReliedUponStatuteQuantity ', $suborg, $agency->field_num_relied_by_agency_comp->value);
        //field_num_relied_by_agency_comp
        
      }
    }
    
    //$this->addComponentData($component_data, $section, 'foia:ReliedUponStatute', 'ES', $map);
    //$this->addProcessingAssociations($component_data, $section, 'foia:ProcessingStatisticsOrganizationAssociation', 'ES');
    ******************************/
    // Add footnote.
    if ($this->node->field_footnotes_iv->value){
      foreach($this->node->field_footnotes_iv as $footnote){
        $footnote = \Drupal\Component\Utility\SafeMarkup::checkPlain($footnote->value);
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
    
    //die();
    $section = $this->addElementNs('foia:AppealDenialOtherReasonSection', $this->root);
    if ( $vic3 ){
      foreach ($vic3 as $delta => $vic3_field){
        //foia:ComponentOtherDenialReason 
        $sec_item = $this->addElementNs('foia:ComponentOtherDenialReason', $section);
        $sec_item->setAttribute('s:id', 'ADOR' . ($delta + 1));
        //dump($vic3_field->get('field_admin_app_vic3_info'));
        //die();
        $item = $this->addElementNs('foia:OtherDenialReason', $sec_item);
        $item_value = $this->addElementNs('foia:OtherDenialReasonDescriptionText', $item, "nested paragraph field date");
        $item1_value = $this->addElementNs('foia:OtherDenialReasonQuantity', $item, "Nested paragraph data");
        
        //$sec_item = $this->addElementNs('foia:AppealDenialOtherReasonSection', $item);
      }
    }
    /*$vic3_desc_oth_reas = $this->node->field_overall_vic3_desc_oth_reas->value;
    if (!empty($vic3_desc_oth_reas)){
      $vic3_desc_oth_reas = \Drupal\Component\Utility\SafeMarkup::checkPlain($vic3_desc_oth_reas);
      $this->addElementNs('foia:OtherDenialReasonDescriptionText', $section, $vic3_desc_oth_reas);
    }
    $vic3_num_relied_up = $this->node->field_overall_vic3_num_relied_up->value;
    if (!empty($vic3_num_relied_up)){
      $vic3_num_relied_up = \Drupal\Component\Utility\SafeMarkup::checkPlain($vic3_num_relied_up);
      $this->addElementNs('foia:OtherDenialReasonDescriptionText', $section, $vic3_num_relied_up);
    }
    $vic3_overall_vic3_total = $this->node->field_overall_vic3_total->value;
    */
   
    $sec2 = $this->addElementNs('foia:OtherDenialReasonOrganizationAssociation', $section);
    $sec2_item = $this->addElementNs('foia:ComponentDataReference', $sec2);
    $sec2_item->setAttribute('s:ref', 'ADOR8');
    $sec2_item1 = $this->addElementNs('nc:OrganizationReference', $sec2);
    $sec2_item1->setAttribute('s:ref', 'ORG2');
    // Add footnote.
    $footnote = \Drupal\Component\Utility\SafeMarkup::checkPlain($this->node->field_footnotes_vic3->value);
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
   // dump($component_data->oldest_days);
    //echo count($component_data);
    //die('as');
    $map = [
      'field_date_1' => 'foia:OldItemReceiptDate',
      'field_num_days_1' => 'foia:OldItemPendingDaysQuantity',
      'field_date_2' => 'foia:OldItemReceiptDate',
      'field_num_days_2' => 'foia:OldItemPendingDaysQuantity',
      'field_date_3' => 'foia:OldItemReceiptDate',
      'field_num_days_3' => 'foia:OldItemPendingDaysQuantity',
      'field_date_4' => 'foia:OldItemReceiptDate',
      'field_num_days_4' => 'foia:OldItemPendingDaysQuantity',
      'field_date_5' => 'foia:OldItemReceiptDate',
      'field_num_days_5' => 'foia:OldItemPendingDaysQuantity',
      'field_date_6' => 'foia:OldItemReceiptDate',
      'field_num_days_6' => 'foia:OldItemPendingDaysQuantity',
      'field_date_7' => 'foia:OldItemReceiptDate',
      'field_num_days_7' => 'foia:OldItemPendingDaysQuantity',
      'field_date_8' => 'foia:OldItemReceiptDate',
      'field_num_days_8' => 'foia:OldItemPendingDaysQuantity',
      'field_date_9' => 'foia:OldItemReceiptDate',
      'field_num_days_9' => 'foia:OldItemPendingDaysQuantity',
      'field_date_10' => 'foia:OldItemReceiptDate',
      'field_num_days_10' => 'foia:OldItemPendingDaysQuantity',
    ];
    $overall_map = [
      'field_overall_vic5_date_1' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_1' => 'nc:OrganizationReference',
      'field_overall_vic5_date_2' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_2' => 'nc:OrganizationReference',
      'field_overall_vic5_date_3' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_3' => 'nc:OrganizationReference',
      'field_overall_vic5_date_4' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_4' => 'nc:OrganizationReference',
      'field_overall_vic5_date_5' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_5' => 'nc:OrganizationReference',
      'field_overall_vic5_date_6' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_6' => 'nc:OrganizationReference',
      'field_overall_vic5_date_7' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_7' => 'nc:OrganizationReference',
      'field_overall_vic5_date_8' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_8' => 'nc:OrganizationReference',
      'field_overall_vic5_date_9' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_9' => 'nc:OrganizationReference',
      'field_overall_vic5_date_10' => 'foia:ComponentDataReference',
      'field_overall_vic5_num_day_10' => 'nc:OrganizationReference',
    ];

    $section = $this->addElementNs('foia:OldestPendingItems', $this->root);
    $this->addComponentData($component_data, $section, 'foia:OldItem', 'OPA', $map, $overall_map);
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      //$item = $this->addElementNs('foia:OldItem', $section);
      //$item->setAttribute('s:id', 'OPA' . ($delta + 1));
      // Add quantity for each exemption code.
     // $this->addLabeledQuantity($component, $item, 'foia:AppliedExemption', 'foia:AppliedExemptionCode', 'foia:OldestPendingItemsOrganizationAssociation', $exemption_map);
    }
    $this->addProcessingAssociations($component_data, $section, 'foia:OldestPendingItemsOrganizationAssociation', 'OPA');
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
