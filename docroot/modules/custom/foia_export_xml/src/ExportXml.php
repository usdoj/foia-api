<?php

namespace Drupal\foia_export_xml;

use Drupal\Component\Utility\SafeMarkup;
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
   * Is this a centralized agency?
   *
   * A centralized agency is one with only one component, corresponding to the
   * agency itself. For such agencies, we do not need to add agency-overall data
   * to the report since those data are already there.
   *
   * @var bool
   */
  protected $isCentralized = FALSE;

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

    // Check whether it is a centralized agency.
    $component_data = $node->field_agency_components->referencedEntities();
    if (count($component_data) == 1) {
      $this->isCentralized = $component_data[0]->field_is_centralized->value;
    }

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
    $element = $this->document->createElementNS($namespaces[$prefix], $local_name);
    if (!is_null($value)) {
      $element->appendChild($this->document->createTextNode($value));
    }
    $parent->appendChild($element);
    return $element;
  }

  /**
   * Add a footnote to a specified section.
   *
   * @param string $field
   *   The name of the footnote field, such as 'field_footnotes_iv'.
   * @param \DOMElement $parent
   *   The parent of the new element.
   */
  protected function addFootnote($field, \DOMElement $parent) {
    $footnote = trim(strip_tags($this->node->get($field)->value));
    if ($footnote && !empty($footnote)) {
      $this->addElementNs('foia:FootnoteText', $parent, SafeMarkup::checkPlain($footnote));
    }
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
    if (!$this->isCentralized) {
      $item = $this->addElementNs($data_tag, $parent);
      $item->setAttribute('s:id', $prefix . '0');
      foreach ($overall_map as $field => $tag) {
        $this->addElementNs($tag, $item, $this->node->get($field)->value);
      }
    }
  }

  /**
   * Add oldest-days data.
   *
   * Add "oldest days" data from an array of paragraphs with per-component data
   * and corresponding overall data from the node.
   *
   * @param Drupal\Core\Entity\EntityInterface[] $component_data
   *   An array of paragraphs with per-component data, each with
   *   field_agency_component referencing an Agency Component node.
   * @param \DOMElement $parent
   *   The parent element to which new nodes will be added.
   * @param string $prefix
   *   The base string used in the s:id attribute.
   * @param string $overall_date
   *   The field name for the overall dates, without the number at the end.
   * @param string $overall_days
   *   The field name for the overall number of days, without the number at the
   *   end.
   */
  protected function addOldestDays(array $component_data, \DOMElement $parent, $prefix, $overall_date, $overall_days) {
    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:OldestPendingItems', $parent);
      $item->setAttribute('s:id', $prefix . ($delta + 1));
      foreach (range(1, 10) as $index) {
        $date = $component->get("field_date_$index")->value;
        $days = $component->get("field_num_days_$index")->value;
        if (preg_match('/^\<1|\d+/', $days)) {
          $old_item = $this->addElementNs('foia:OldItem', $item);
          $this->addElementNs('foia:OldItemReceiptDate', $old_item, $date);
          $this->addElementNs('foia:OldItemPendingDaysQuantity', $old_item, $days);
        }
      }
    }

    // Add overall data.
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:OldestPendingItems', $parent);
      $item->setAttribute('s:id', $prefix . 0);
      foreach (range(1, 10) as $index) {
        $date = $this->node->get($overall_date . $index)->value;
        $days = $this->node->get($overall_days . $index)->value;
        if (preg_match('/^\<1|\d+/', $days)) {
          $old_item = $this->addElementNs('foia:OldItem', $item);
          $this->addElementNs('foia:OldItemReceiptDate', $old_item, $date);
          $this->addElementNs('foia:OldItemPendingDaysQuantity', $old_item, $days);
        }
      }
    }
  }

  /**
   * Add response-time data.
   *
   * Add "response time" data from a node or paragraph.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   A node or paragraph with response-time data.
   * @param \DOMElement $parent
   *   The parent element to which new nodes will be added.
   * @param string $field_prefix
   *   The field name for the data, without the 'sim_med' or similar suffix.
   */
  protected function addResponseTimes(EntityInterface $entity, \DOMElement $parent, $field_prefix) {
    $tracks = [
      'sim_' => 'SimpleResponseTime',
      'comp_' => 'ComplexResponseTime',
      'exp_' => 'ExpeditedResponseTime',
    ];
    $types = [
      'med' => 'ResponseTimeMedianDaysValue',
      'avg' => 'ResponseTimeAverageDaysValue',
      'low' => 'ResponseTimeLowestDaysValue',
      'high' => 'ResponseTimeHighestDaysValue',
    ];

    foreach ($tracks as $key => $local_name) {
      $item = $this->addElementNs("foia:$local_name", $parent);
      foreach ($types as $suffix => $tag) {
        $value = $entity->get($field_prefix . $key . $suffix)->value;
        if ($value) {
          $this->addElementNs("foia:$tag", $item, $value);
        }
      }
    }
  }

  /**
   * Add response-time increments data.
   *
   * Add "response time increments" data from a node or paragraph.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   A node or paragraph with response-time-increments data.
   * @param \DOMElement $parent
   *   The parent element to which new nodes will be added.
   * @param string $field_prefix
   *   The field name for the data, without the '1_20_days' or similar suffix.
   */
  protected function addResponseTimeIncrements(EntityInterface $entity, \DOMElement $parent, $field_prefix) {
    $fields = [
      '1_20_days' => '1-20',
      '21_40_days' => '21-40',
      '41_60_days' => '41-60',
      '61_80_days' => '61-80',
      '81_100_days' => '81-100',
      '101_120_days' => '101-120',
      '121_140_days' => '121-140',
      '141_160_days' => '141-160',
      '161_180_days' => '161-180',
      '181_200_days' => '181-200',
      '201_300_days' => '201-300',
      '301_400_days' => '301-400',
      '400_up_days' => '401+',
    ];
    foreach ($fields as $suffix => $label) {
      $item = $this->addElementNs('foia:TimeIncrement', $parent);
      $this->addElementNs('foia:TimeIncrementCode', $item, $label);
      $this->addElementNs('foia:TimeIncrementProcessedQuantity', $item, $entity->get($field_prefix . $suffix)->value);
    }
    $this->addElementNs('foia:TimeIncrementTotalQuantity', $parent, $entity->get($field_prefix . 'total')->value);
  }

  /**
   * Add pending-request data.
   *
   * Add pending-request data from a node or paragraph.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   A node or paragraph with pending-request data.
   * @param \DOMElement $parent
   *   The parent element to which new nodes will be added.
   * @param string $field_prefix
   *   The field name for the data, without the 'sim_pend' or similar suffix.
   */
  protected function addPendingRequests(EntityInterface $entity, \DOMElement $parent, $field_prefix) {
    $tracks = [
      'sim_' => 'SimplePendingRequestStatistics',
      'comp_' => 'ComplexPendingRequestStatistics',
      'exp_' => 'ExpeditedPendingRequestStatistics',
    ];

    foreach ($tracks as $key => $local_name) {
      $item = $this->addElementNs("foia:$local_name", $parent);
      $pending = $entity->get($field_prefix . $key . 'pend')->value;
      $this->addElementNs('foia:PendingRequestQuantity', $item, $pending);
      if ($pending) {
        $this->addElementNs('foia:PendingRequestMedianDaysValue', $item, $entity->get($field_prefix . $key . 'med')->value);
        $this->addElementNs('foia:PendingRequestAverageDaysValue', $item, $entity->get($field_prefix . $key . 'avg')->value);
      }
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
      if (empty($component->field_agency_component)) {
        continue;
      }

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
    if (!$this->isCentralized) {
      $matchup = $this->addElementNs($tag, $parent);
      $this
        ->addElementNs('foia:ComponentDataReference', $matchup)
        ->setAttribute('s:ref', $prefix . 0);
      $this
        ->addElementNs('nc:OrganizationReference', $matchup)
        ->setAttribute('s:ref', 'ORG' . 0);
    }
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
    if ($this->isCentralized) {
      $component = $this->node->field_agency_components->referencedEntities()[0];
      $this->componentMap[$component->id()] = 'ORG0';
    }
    else {
      foreach ($this->node->field_agency_components->referencedEntities() as $delta => $component) {
        $local_id = 'ORG' . ($delta + 1);
        $this->componentMap[$component->id()] = $local_id;

        $suborg = $this->addElementNs('nc:OrganizationSubUnit', $org);
        $suborg->setAttribute('s:id', $local_id);
        $item = $this->addElementNs('nc:OrganizationAbbreviationText', $suborg, $component->field_agency_comp_abbreviation->value);
        $item = $this->addElementNs('nc:OrganizationName', $suborg, $component->label());
      }
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
    $statutes = $this->node->field_statute_iv->referencedEntities();
    $statuteSection = $this->addElementNs('foia:Exemption3StatuteSection', $this->root);

    // Add information about each statute.
    foreach ($statutes as $delta => $statute) {
      $local_id = 'ES' . ($delta + 1);
      $suborg = $this->addElementNs('foia:ReliedUponStatute', $statuteSection);
      $suborg->setAttribute('s:id', $local_id);
      $this->addElementNs('j:StatuteDescriptionText', $suborg, $statute->field_statute->value);
      $info_withheld = SafeMarkup::checkPlain($statute->field_type_of_info_withheld->value);
      $this->addElementNs('foia:ReliedUponStatuteInformationWithheldText', $suborg, $info_withheld);
      $itemCase = $this->addElementNs('nc:Case', $suborg);
      $this->addElementNs('nc:CaseTitleText', $itemCase, $statute->field_case_citation->value);
    }

    foreach ($statutes as $delta => $statute) {
      $local_id = 'ES' . ($delta + 1);
      $components = $statute->field_agency_component_inf->referencedEntities();
      // Add component data for each statute.
      foreach ($components as $component_info) {
        $agency_component = $component_info->field_agency_component->referencedEntities()[0];
        $item = $this->addElementNs('foia:ReliedUponStatuteOrganizationAssociation', $statuteSection);
        $this->addElementNs('foia:ComponentDataReference', $item)
          ->setAttribute('s:ref', $local_id);
        $this->addElementNs('nc:OrganizationReference', $item)
          ->setAttribute('s:ref', $this->componentMap[$agency_component->id()]);
        $this->addElementNs('foia:ReliedUponStatuteQuantity', $item, $component_info->field_num_relied_by_agency_comp->value);
      }
      // Add agency overall data for the statute.
      $item = $this->addElementNs('foia:ReliedUponStatuteOrganizationAssociation', $statuteSection);
      $this->addElementNs('foia:ComponentDataReference', $item)
        ->setAttribute('s:ref', $local_id);
      $this->addElementNs('nc:OrganizationReference', $item)
        ->setAttribute('s:ref', 'ORG0');
      $this->addElementNs('foia:ReliedUponStatuteQuantity', $item, $statute->field_total_num_relied_by_agency->value);
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
    $this->addFootnote('field_footnotes_va', $section);
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
    ];
    $overall_map = [
      'field_overall_vb1_full_grants' => 'foia:RequestDispositionFullGrantQuantity',
      'field_overall_vb1_part_grants_de' => 'foia:RequestDispositionPartialGrantQuantity',
      'field_overall_vb1_full_denials_e' => 'foia:RequestDispositionFullExemptionDenialQuantity',
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
      // Add Request Disposition Total.
      $this->addElementNs('foia:RequestDispositionTotalQuantity', $item, $component->get('field_total')->value);
    }

    // Add overall data.
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:RequestDisposition', $section);
      $item->setAttribute('s:id', 'RD' . 0);
      foreach ($overall_map as $field => $tag) {
        $this->addElementNs($tag, $item, $this->node->get($field)->value);
      }
      // Add quantity for each denial reason.
      $this->addLabeledQuantity($this->node, $item, 'foia:NonExemptionDenial', 'foia:NonExemptionDenialReasonCode', 'foia:NonExemptionDenialQuantity', $overall_reason_map);
      // Add Request Disposition Total.
      $total = isset($component) && !is_null($component)
        ? $component->get('field_total')->value
        : NULL;
      $this->addElementNs('foia:RequestDispositionTotalQuantity', $item, $total);
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:RequestDispositionOrganizationAssociation', 'RD');
    $this->addFootnote('field_footnotes_vb1', $section);
  }

  /**
   * Request Denial Other Reason Section.
   *
   * This corresponds to Section V.B(2) of the annual report.
   */
  protected function requestDenialOtherReasonSection() {
    $component_data = $this->node->field_foia_requests_vb2->referencedEntities();
    $section = $this->addElementNs('foia:RequestDenialOtherReasonSection', $this->root);

    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentOtherDenialReason', $section);
      $item->setAttribute('s:id', 'CODR' . ($delta + 1));
      foreach ($component->field_foia_req_vb2_info->referencedEntities() as $reason) {
        $item12 = $this->addElementNs('foia:OtherDenialReason', $item);
        $this->addElementNs('foia:OtherDenialReasonDescriptionText', $item12, $reason->field_desc_oth_reasons->value);
        $this->addElementNs('foia:OtherDenialReasonQuantity', $item12, $reason->field_num_relied_upon->value);
      }
      $this->addElementNs('foia:ComponentOtherDenialReasonQuantity', $item, $component->field_total->value);
    }

    // Add data for the agency overall.
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:ComponentOtherDenialReason', $section);
      $item->setAttribute('s:id', 'CODR' . 0);
      $this->addElementNs('foia:ComponentOtherDenialReasonQuantity', $item, $this->node->get('field_overall_vb2_total')->value);
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:OtherDenialReasonOrganizationAssociation', 'CODR');
    $this->addFootnote('field_footnotes_vb2', $section);
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
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:ComponentAppliedExemptions', $section);
      $item->setAttribute('s:id', 'RDE' . 0);
      // Add quantity for each exemption code.
      $this->addLabeledQuantity($this->node, $item, 'foia:AppliedExemption', 'foia:AppliedExemptionCode', 'foia:AppliedExemptionQuantity', $overall_exemption_map);
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:ComponentAppliedExemptionsOrganizationAssociation', 'RDE');
    $this->addFootnote('field_footnotes_vb3', $section);
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
    $this->addFootnote('field_footnotes_via', $section);
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
    $this->addFootnote('field_footnotes_vib', $section);
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
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:ComponentAppliedExemptions', $section);
      $item->setAttribute('s:id', 'ADE' . 0);
      // Add quantity for each exemption code.
      $this->addLabeledQuantity($this->node, $item, 'foia:AppliedExemption', 'foia:AppliedExemptionCode', 'foia:AppliedExemptionQuantity', $overall_exemption_map);
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:ComponentAppliedExemptionsOrganizationAssociation', 'ADE');
    $this->addFootnote('field_footnotes_vic1', $section);
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
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:AppealNonExemptionDenial', $section);
      $item->setAttribute('s:id', 'ANE' . 0);
      $this->addLabeledQuantity($this->node, $item, 'foia:NonExemptionDenial', 'foia:NonExemptionDenialReasonCode', 'foia:NonExemptionDenialQuantity', $overall_reason_map);
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:AppealNonExemptionDenialOrganizationAssociation', 'ANE');
    $this->addFootnote('field_footnotes_vic2', $section);
  }

  /**
   * Appeal Denial Other Reason Section.
   *
   * This corresponds to Section VI.C(3) of the annual report.
   */
  protected function appealDenialOtherReasonSection() {
    $component_data = $this->node->field_admin_app_vic3->referencedEntities();

    $section = $this->addElementNs('foia:AppealDenialOtherReasonSection', $this->root);

    // Add data for each component.
    if ($component_data) {
      foreach ($component_data as $delta => $component) {
        $reason_section = $this->addElementNs('foia:ComponentOtherDenialReason', $section);
        $reason_section->setAttribute('s:id', 'ADOR' . ($delta + 1));
        foreach ($component->field_admin_app_vic3_info->referencedEntities() as $reason) {
          $item = $this->addElementNs('foia:OtherDenialReason', $reason_section);
          $this->addElementNs('foia:OtherDenialReasonDescriptionText', $item, $reason->field_desc_oth_reasons->value);
          $this->addElementNs('foia:OtherDenialReasonQuantity', $item, $reason->field_num_relied_upon->value);
        }
        $this->addElementNs('foia:ComponentOtherDenialReasonQuantity', $reason_section, $component->field_total->value);
      }
    }

    // Add data for the agency overall.
    if (!$this->isCentralized) {
      $reason_section = $this->addElementNs('foia:ComponentOtherDenialReason', $section);
      $reason_section->setAttribute('s:id', 'ADOR' . 0);
      $this->addElementNs('foia:ComponentOtherDenialReasonQuantity', $reason_section, $this->node->field_overall_vic3_total->value);
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:OtherDenialReasonOrganizationAssociation', 'ADOR');
    $this->addFootnote('field_footnotes_vic3', $section);
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
    $this->addFootnote('field_footnotes_vic4', $section);
  }

  /**
   * Oldest Pending Appeal Section.
   *
   * This corresponds to Section VI.C(5) of the annual report.
   */
  protected function oldestPendingAppealSection() {
    $component_data = $this->node->field_admin_app_vic5->referencedEntities();
    $section = $this->addElementNs('foia:OldestPendingAppealSection', $this->root);
    $this->addOldestDays($component_data, $section, 'OPA', 'field_overall_vic5_date_', 'field_overall_vic5_num_day_');
    $this->addProcessingAssociations($component_data, $section, 'foia:OldestPendingItemsOrganizationAssociation', 'OPA');
    $this->addFootnote('field_footnotes_vic5', $section);
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
      $item = $this->addElementNs('foia:ProcessedResponseTime', $section);
      $item->setAttribute('s:id', 'PRT' . ($delta + 1));
      $this->addResponseTimes($component, $item, 'field_');
    }

    // Add data for the agency overall.
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:ProcessedResponseTime', $section);
      $item->setAttribute('s:id', 'PRT' . 0);
      $this->addResponseTimes($this->node, $item, 'field_overall_viia_');
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:ProcessedResponseTimeOrganizationAssociation', 'PRT');
    $this->addFootnote('field_footnotes_viia', $section);
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
      $item = $this->addElementNs('foia:ProcessedResponseTime', $section);
      $item->setAttribute('s:id', 'IGR' . ($delta + 1));
      $this->addResponseTimes($component, $item, 'field_');
    }

    // Add data for the agency overall.
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:ProcessedResponseTime', $section);
      $item->setAttribute('s:id', 'IGR' . 0);
      $this->addResponseTimes($this->node, $item, 'field_overall_viib_');
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:ProcessedResponseTimeOrganizationAssociation', 'IGR');
    $this->addFootnote('field_footnotes_viib', $section);
  }

  /**
   * Simple Response Time Increments Section.
   *
   * This corresponds to Section VII.C(1) of the annual report.
   */
  protected function simpleResponseTimeIncrementsSection() {
    $component_data = $this->node->field_proc_req_viic1->referencedEntities();
    $section = $this->addElementNs('foia:SimpleResponseTimeIncrementsSection', $this->root);

    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentResponseTimeIncrements', $section);
      $item->setAttribute('s:id', 'SRT' . ($delta + 1));
      $this->addResponseTimeIncrements($component, $item, 'field_');
    }

    // Add data for the agency overall.
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:ComponentResponseTimeIncrements', $section);
      $item->setAttribute('s:id', 'SRT' . 0);
      $this->addResponseTimeIncrements($this->node, $item, 'field_overall_viic1_');
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:ResponseTimeIncrementsOrganizationAssociation', 'SRT');
    $this->addFootnote('field_footnotes_viic1', $section);
  }

  /**
   * Complex Response Time Increments Section.
   *
   * This corresponds to Section VII.C(2) of the annual report.
   */
  protected function complexResponseTimeIncrementsSection() {
    $component_data = $this->node->field_proc_req_viic2->referencedEntities();
    $section = $this->addElementNs('foia:ComplexResponseTimeIncrementsSection', $this->root);

    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentResponseTimeIncrements', $section);
      $item->setAttribute('s:id', 'CRT' . ($delta + 1));
      $this->addResponseTimeIncrements($component, $item, 'field_');
    }

    // Add data for the agency overall.
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:ComponentResponseTimeIncrements', $section);
      $item->setAttribute('s:id', 'CRT' . 0);
      $this->addResponseTimeIncrements($this->node, $item, 'field_overall_viic2_');
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:ResponseTimeIncrementsOrganizationAssociation', 'CRT');
    $this->addFootnote('field_footnotes_viic2', $section);
  }

  /**
   * Expedited Response Time Increments Section.
   *
   * This corresponds to Section VII.C(3) of the annual report.
   */
  protected function expeditedResponseTimeIncrementsSection() {
    $component_data = $this->node->field_proc_req_viic3->referencedEntities();
    $section = $this->addElementNs('foia:ExpeditedResponseTimeIncrementsSection', $this->root);

    // Add data for each component.
    foreach ($component_data as $delta => $component) {
      $item = $this->addElementNs('foia:ComponentResponseTimeIncrements', $section);
      $item->setAttribute('s:id', 'ERT' . ($delta + 1));
      $this->addResponseTimeIncrements($component, $item, 'field_');
    }

    // Add data for each component.
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:ComponentResponseTimeIncrements', $section);
      $item->setAttribute('s:id', 'ERT' . 0);
      $this->addResponseTimeIncrements($this->node, $item, 'field_overall_viic3_');
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:ResponseTimeIncrementsOrganizationAssociation', 'ERT');
    $this->addFootnote('field_footnotes_viic3', $section);
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
      $item = $this->addElementNs('foia:PendingPerfectedRequests', $section);
      $item->setAttribute('s:id', 'PPR' . ($delta + 1));
      $this->addPendingRequests($component, $item, 'field_');
    }

    // Add data for the agency overall.
    if (!$this->isCentralized) {
      $item = $this->addElementNs('foia:PendingPerfectedRequests', $section);
      $item->setAttribute('s:id', 'PPR' . 0);
      $this->addPendingRequests($this->node, $item, 'field_overall_viid_');
    }

    $this->addProcessingAssociations($component_data, $section, 'foia:PendingPerfectedRequestsOrganizationAssociation', 'PPR');
    $this->addFootnote('field_footnotes_viid', $section);
  }

  /**
   * Oldest Pending Request Section.
   *
   * This corresponds to Section VII.E of the annual report.
   */
  protected function oldestPendingRequestSection() {
    $component_data = $this->node->field_admin_app_viie->referencedEntities();
    $section = $this->addElementNs('foia:OldestPendingRequestSection', $this->root);
    $this->addOldestDays($component_data, $section, 'OPR', 'field_overall_viie_date_', 'field_overall_viie_num_days_');
    $this->addProcessingAssociations($component_data, $section, 'foia:OldestPendingItemsOrganizationAssociation', 'OPR');
    $this->addFootnote('field_footnotes_viie', $section);
  }

  /**
   * Expedited Processing Section.
   *
   * This corresponds to Section VIII.A of the annual report.
   */
  protected function expeditedProcessingSection() {
    $component_data = $this->node->field_req_viiia->referencedEntities();
    $map = [
      'field_num_grant' => 'foia:RequestGrantedQuantity',
      'field_num_denied' => 'foia:RequestDeniedQuantity',
      'field_med_days_jud' => 'foia:AdjudicationMedianDaysValue',
      'field_avg_days_jud' => 'foia:AdjudicationAverageDaysValue',
      'field_num_jud_w10' => 'foia:AdjudicationWithinTenDaysQuantity',
    ];
    $overall_map = [
      'field_overall_viiia_num_grant' => 'foia:RequestGrantedQuantity',
      'field_overall_viiia_num_denied' => 'foia:RequestDeniedQuantity',
      'field_overall_viiia_med_days_jud' => 'foia:AdjudicationMedianDaysValue',
      'field_overall_viiia_avg_days_jud' => 'foia:AdjudicationAverageDaysValue',
      'field_overall_viiia_num_jud_w10' => 'foia:AdjudicationWithinTenDaysQuantity',
    ];

    $section = $this->addElementNs('foia:ExpeditedProcessingSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:ExpeditedProcessing', 'EP', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:ExpeditedProcessingOrganizationAssociation', 'EP');
    $this->addFootnote('field_footnotes_viiia', $section);
  }

  /**
   * Fee Waiver Section.
   *
   * This corresponds to Section VIII.B of the annual report.
   */
  protected function feeWaiverSection() {
    $component_data = $this->node->field_req_viiib->referencedEntities();
    $map = [
      'field_num_grant' => 'foia:RequestGrantedQuantity',
      'field_num_denied' => 'foia:RequestDeniedQuantity',
      'field_med_days_jud' => 'foia:AdjudicationMedianDaysValue',
      'field_avg_days_jud' => 'foia:AdjudicationAverageDaysValue',
    ];
    $overall_map = [
      'field_overall_viiib_num_grant' => 'foia:RequestGrantedQuantity',
      'field_overall_viiib_num_denied' => 'foia:RequestDeniedQuantity',
      'field_overall_viiib_med_days_jud' => 'foia:AdjudicationMedianDaysValue',
      'field_overall_viiib_avg_days_jud' => 'foia:AdjudicationAverageDaysValue',
    ];

    $section = $this->addElementNs('foia:FeeWaiverSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:FeeWaiver', 'FW', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:FeeWaiverOrganizationAssociation', 'FW');
    $this->addFootnote('field_footnotes_viiib', $section);
  }

  /**
   * Personnel And Cost Section.
   *
   * This corresponds to Section IX of the annual report.
   */
  protected function personnelAndCostSection() {
    $component_data = $this->node->field_foia_pers_costs_ix->referencedEntities();
    $map = [
      'field_full_emp' => 'foia:FullTimeEmployeeQuantity',
      'field_eq_full_emp' => 'foia:EquivalentFullTimeEmployeeQuantity',
      'field_total_staff' => 'foia:TotalFullTimeStaffQuantity',
      'field_proc_costs' => 'foia:ProcessingCostAmount',
      'field_lit_costs' => 'foia:LitigationCostAmount',
      'field_total_costs' => 'foia:TotalCostAmount',
    ];
    $overall_map = [
      'field_overall_ix_full_emp' => 'foia:FullTimeEmployeeQuantity',
      'field_overall_ix_eq_full_emp' => 'foia:EquivalentFullTimeEmployeeQuantity',
      'field_overall_ix_total_staff' => 'foia:TotalFullTimeStaffQuantity',
      'field_overall_ix_proc_costs' => 'foia:ProcessingCostAmount',
      'field_overall_ix_lit_costs' => 'foia:LitigationCostAmount',
      'field_overall_ix_total_costs' => 'foia:TotalCostAmount',
    ];

    $section = $this->addElementNs('foia:PersonnelAndCostSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:PersonnelAndCost', 'PC', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:PersonnelAndCostOrganizationAssociation', 'PC');
    $this->addFootnote('field_footnotes_ix', $section);
  }

  /**
   * Fees Collected Section.
   *
   * This corresponds to Section X of the annual report.
   */
  protected function feesCollectedSection() {
    $component_data = $this->node->field_fees_x->referencedEntities();
    $map = [
      'field_total_fees' => 'foia:FeesCollectedAmount',
      'field_perc_costs' => 'foia:FeesCollectedCostPercent',
    ];
    $overall_map = [
      'field_overall_x_total_fees' => 'foia:FeesCollectedAmount',
      'field_overall_x_perc_costs' => 'foia:FeesCollectedCostPercent',
    ];

    $section = $this->addElementNs('foia:FeesCollectedSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:FeesCollected', 'FC', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:FeesCollectedOrganizationAssociation', 'FC');
    $this->addFootnote('field_footnotes_x', $section);
  }

  /**
   * Subsection Used Section.
   *
   * This corresponds to Section XI.A of the annual report.
   */
  protected function subsectionUsedSection() {
    $component_data = $this->node->field_sub_xia->referencedEntities();
    $map = [
      'field_sub_used' => 'foia:TimesUsedQuantity',
    ];
    $overall_map = [
      'field_overall_xia_sub_used' => 'foia:TimesUsedQuantity',
    ];

    $section = $this->addElementNs('foia:SubsectionUsedSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:SubsectionUsed', 'SU', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:SubsectionUsedOrganizationAssociation', 'SU');
    $this->addFootnote('field_footnotes_xia', $section);
  }

  /**
   * Subsection Post Section.
   *
   * This corresponds to Section XI.B of the annual report.
   */
  protected function subsectionPostSection() {
    $component_data = $this->node->field_sub_xib->referencedEntities();
    $map = [
      'field_rec_post_foia' => 'foia:PostedbyFOIAQuantity',
      'field_rec_post_prog' => 'foia:PostedbyProgramQuantity',
    ];
    $overall_map = [
      'field_overall_xib_rec_post_foia' => 'foia:PostedbyFOIAQuantity',
      'field_overall_xib_rec_post_prog' => 'foia:PostedbyProgramQuantity',
    ];

    $section = $this->addElementNs('foia:SubsectionPostSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:Subsection', 'SP', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:SubsectionPostOrganizationAssociation', 'SP');
    $this->addFootnote('field_footnotes_xib', $section);
  }

  /**
   * Backlog Section.
   *
   * This corresponds to Section XII.A of the annual report.
   */
  protected function backlogSection() {
    $component_data = $this->node->field_foia_xiia->referencedEntities();
    $map = [
      'field_back_req_end_yr' => 'foia:BackloggedRequestQuantity',
      'field_back_app_end_yr' => 'foia:BackloggedAppealQuantity',
    ];
    $overall_map = [
      'field_overall_xiia_back_req_end_' => 'foia:BackloggedRequestQuantity',
      'field_overall_xiia_back_app_end_' => 'foia:BackloggedAppealQuantity',
    ];

    $section = $this->addElementNs('foia:BacklogSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:Backlog', 'BK', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:BacklogOrganizationAssociation', 'BK');
    $this->addFootnote('field_footnotes_xiia', $section);
  }

  /**
   * Processed Consultation Section.
   *
   * This corresponds to Section XII.B of the annual report.
   */
  protected function processedConsultationSection() {
    $component_data = $this->node->field_foia_xiib->referencedEntities();
    $map = [
      'field_pend_start_yr' => 'foia:ProcessingStatisticsPendingAtStartQuantity',
      'field_con_during_yr' => 'foia:ProcessingStatisticsReceivedQuantity',
      'field_proc_start_yr' => 'foia:ProcessingStatisticsProcessedQuantity',
      'field_pend_end_yr' => 'foia:ProcessingStatisticsPendingAtEndQuantity',
    ];
    $overall_map = [
      'field_overall_xiib_pend_start_yr' => 'foia:ProcessingStatisticsPendingAtStartQuantity',
      'field_overall_xiib_con_during_yr' => 'foia:ProcessingStatisticsReceivedQuantity',
      'field_overall_xiib_proc_start_yr' => 'foia:ProcessingStatisticsProcessedQuantity',
      'field_overall_xiib_pend_end_yr' => 'foia:ProcessingStatisticsPendingAtEndQuantity',
    ];

    $section = $this->addElementNs('foia:ProcessedConsultationSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:ProcessingStatistics', 'PCN', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:ProcessingStatisticsOrganizationAssociation', 'PCN');
    $this->addFootnote('field_footnotes_xiib', $section);
  }

  /**
   * Oldest Pending Consultation Section.
   *
   * This corresponds to Section XII.C of the annual report.
   */
  protected function oldestPendingConsultationSection() {
    $component_data = $this->node->field_foia_xiic->referencedEntities();
    $section = $this->addElementNs('foia:OldestPendingConsultationSection', $this->root);
    $this->addOldestDays($component_data, $section, 'OPC', 'field_overall_xiic_date_', 'field_overall_xiic_num_days_');
    $this->addProcessingAssociations($component_data, $section, 'foia:OldestPendingItemsOrganizationAssociation', 'OPC');
    $this->addFootnote('field_footnotes_xiic', $section);
  }

  /**
   * Processed Request Comparison Section.
   *
   * This corresponds to Section XII.D(1) of the annual report.
   */
  protected function processedRequestComparisonSection() {
    $component_data = $this->node->field_foia_xiid1->referencedEntities();
    $map = [
      'field_received_last_yr' => 'foia:ItemsReceivedLastYearQuantity',
      'field_received_cur_yr' => 'foia:ItemsReceivedCurrentYearQuantity',
      'field_proc_last_yr' => 'foia:ItemsProcessedLastYearQuantity',
      'field_proc_cur_yr' => 'foia:ItemsProcessedCurrentYearQuantity',
    ];
    $overall_map = [
      'field_overall_xiid1_received_las' => 'foia:ItemsReceivedLastYearQuantity',
      'field_overall_xiid1_received_cur' => 'foia:ItemsReceivedCurrentYearQuantity',
      'field_overall_xiid1_proc_last_yr' => 'foia:ItemsProcessedLastYearQuantity',
      'field_overall_xiid1_proc_cur_yr' => 'foia:ItemsProcessedCurrentYearQuantity',
    ];

    $section = $this->addElementNs('foia:ProcessedRequestComparisonSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:ProcessingComparison', 'RPC', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:ProcessingComparisonOrganizationAssociation', 'RPC');
    $this->addFootnote('field_footnotes_xiid1', $section);
  }

  /**
   * Backlogged Request Comparison Section.
   *
   * This corresponds to Section XII.D(2) of the annual report.
   */
  protected function backloggedRequestComparisonSection() {
    $component_data = $this->node->field_foia_xiid2->referencedEntities();
    $map = [
      'field_back_prev_yr' => 'foia:BacklogLastYearQuantity',
      'field_back_cur_yr' => 'foia:BacklogCurrentYearQuantity',
    ];
    $overall_map = [
      'field_overall_xiid2_back_prev_yr' => 'foia:BacklogLastYearQuantity',
      'field_overall_xiid2_back_cur_yr' => 'foia:BacklogCurrentYearQuantity',
    ];

    $section = $this->addElementNs('foia:BackloggedRequestComparisonSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:BacklogComparison', 'RBC', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:BacklogComparisonOrganizationAssociation', 'RBC');
    $this->addFootnote('field_footnotes_xiid2', $section);
  }

  /**
   * Processed Appeal Comparison Section.
   *
   * This corresponds to Section XII.E(1) of the annual report.
   */
  protected function processedAppealComparisonSection() {
    $component_data = $this->node->field_foia_xiie1->referencedEntities();
    $map = [
      'field_received_last_yr' => 'foia:ItemsReceivedLastYearQuantity',
      'field_received_cur_yr' => 'foia:ItemsReceivedCurrentYearQuantity',
      'field_proc_last_yr' => 'foia:ItemsProcessedLastYearQuantity',
      'field_proc_cur_yr' => 'foia:ItemsProcessedCurrentYearQuantity',
    ];
    $overall_map = [
      'field_overall_xiie1_received_las' => 'foia:ItemsReceivedLastYearQuantity',
      'field_overall_xiie1_received_cur' => 'foia:ItemsReceivedCurrentYearQuantity',
      'field_overall_xiie1_proc_last_yr' => 'foia:ItemsProcessedLastYearQuantity',
      'field_overall_xiie1_proc_cur_yr' => 'foia:ItemsProcessedCurrentYearQuantity',
    ];

    $section = $this->addElementNs('foia:ProcessedAppealComparisonSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:ProcessingComparison', 'APC', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:ProcessingComparisonOrganizationAssociation', 'APC');
    $this->addFootnote('field_footnotes_xiie1', $section);
  }

  /**
   * Backlogged Appeal Comparison Section.
   *
   * This corresponds to Section XII.E(2) of the annual report.
   */
  protected function backloggedAppealComparisonSection() {
    $component_data = $this->node->field_foia_xiie2->referencedEntities();
    $map = [
      'field_back_prev_yr' => 'foia:BacklogLastYearQuantity',
      'field_back_cur_yr' => 'foia:BacklogCurrentYearQuantity',
    ];
    $overall_map = [
      'field_overall_xiie2_back_prev_yr' => 'foia:BacklogLastYearQuantity',
      'field_overall_xiie2_back_cur_yr' => 'foia:BacklogCurrentYearQuantity',
    ];

    $section = $this->addElementNs('foia:BackloggedAppealComparisonSection', $this->root);
    $this->addComponentData($component_data, $section, 'foia:BacklogComparison', 'ABC', $map, $overall_map);
    $this->addProcessingAssociations($component_data, $section, 'foia:BacklogComparisonOrganizationAssociation', 'ABC');
    $this->addFootnote('field_footnotes_xiie2', $section);
  }

}
