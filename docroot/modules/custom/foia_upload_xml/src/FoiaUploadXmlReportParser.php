<?php

namespace Drupal\foia_upload_xml;

use Drupal\file\FileInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\migrate_plus\DataParserPluginManager;

/**
 * Default configuration to parse and retrieve key fields from upload files.
 *
 * @package Drupal\foia_upload_xml
 */
class FoiaUploadXmlReportParser {

  /**
   * The data parser plugin manager.
   *
   * @var \Drupal\migrate_plus\DataParserPluginManager
   */
  protected $dataParserPluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * SimpleXml data parser configuration for fetching report data.
   *
   * @var array
   */
  protected $simpleXmlParserConfig = [
    'data_fetcher_plugin' => 'file',
    'namespaces' => [
      'iepd' => 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/exchange/1.03',
      'foia' => 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03',
      'nc' => 'http://niem.gov/niem/niem-core/2.0',
    ],
    'item_selector' => '/iepd:FoiaAnnualReport/foia:DocumentFiscalYearDate|/iepd:FoiaAnnualReport/nc:Organization/nc:OrganizationAbbreviationText',
    'fields' => [
      [
        'name' => 'report_year',
        'selector' => '/iepd:FoiaAnnualReport/foia:DocumentFiscalYearDate',
      ],
      [
        'name' => 'agency',
        'selector' => '/iepd:FoiaAnnualReport/nc:Organization/nc:OrganizationAbbreviationText',
      ],
    ],
    'ids' => [
      "report_year" => ['type' => 'integer'],
      "agency" => ['type' => 'string'],
    ],
  ];

  /**
   * FoiaUploadXmlReportParser constructor.
   *
   * @param \Drupal\migrate_plus\DataParserPluginManager $dataParserPluginManager
   *   The data parser plugin manager service.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(DataParserPluginManager $dataParserPluginManager, EntityTypeManager $entityTypeManager) {
    $this->dataParserPluginManager = $dataParserPluginManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Get xml data from an uploaded annual report xml file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The report file to parse.
   *
   * @return mixed
   *   An array of data from the xml file given.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function parse(FileInterface $file) {
    $this->simpleXmlParserConfig['urls'] = [$file->getFileUri()];
    $simple_xml_parser = $this->dataParserPluginManager->createInstance('simple_xml', $this->simpleXmlParserConfig);

    $simple_xml_parser->rewind();
    $data = $simple_xml_parser->current();
    unset($simple_xml_parser);

    $data['agency_tid'] = $this->getAgencyFromAbbreviation($data['agency'] ?? FALSE);

    return $data;
  }

  /**
   * Lookup an agency taxonomy term id from the agency's abbreviated name.
   *
   * @param string $agency_abbreviation
   *   An agency's abbreviated name.
   *
   * @return bool|mixed
   *   The agency's taxonomy term id or FALSE if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getAgencyFromAbbreviation($agency_abbreviation) {
    if (!$agency_abbreviation) {
      return FALSE;
    }

    $term_query = $this->entityTypeManager->getStorage('taxonomy_term')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('vid', 'agency')
      ->condition('field_agency_abbreviation', $agency_abbreviation);

    $tids = $term_query->execute();

    return !empty($tids) ? reset($tids) : FALSE;
  }

}
