<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\FeesCollectedAggregator;
use Drupal\foia_raw_data_to_report\PersonnelAndCostAggregator;
use Drupal\foia_raw_data_to_report\SectionDataCsvValidator;
use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks decimal aggregation and Section IX-XI XML formatting.
 */
#[Group('foia_raw_data_to_report')]
class PersonnelAndCostAggregatorTest extends UnitTestCase {

  /**
   * Preserves precision until XML output and leaves fee ratios unchanged.
   */
  public function testDecimalXml(): void {
    $path = tempnam(sys_get_temp_dir(), 'section-decimals-');
    try {
      $stream = fopen($path, 'wb');
      fputcsv($stream, SectionDataCsvValidator::HEADERS, ',', '"', '');
      fputcsv($stream, ['1.234', '2.345', '10.005', '20.004', '3.005', 0, 0, 0], ',', '"', '');
      fclose($stream);
      $statistics = (new PersonnelAndCostAggregator())->aggregate([
        ['component_id' => 1, 'uri' => $path],
        ['component_id' => 2, 'uri' => $path],
      ]);
      $this->assertSame('2.468', $statistics['overall']['FullTimeEmployeeQuantity']);
      $this->assertSame('60.018', $statistics['overall']['TotalCostAmount']);
      $fees = (new FeesCollectedAggregator())->aggregate($statistics);
      $document = new \DOMDocument();
      $root = $document->createElement('report');
      $document->appendChild($root);
      $builder = new XmlReportBuilder();
      $map = [1 => 'ORG1', 2 => 'ORG2'];
      (new \ReflectionMethod($builder, 'addPersonnelAndCost'))->invoke($builder, $document, $root, $map, $statistics);
      (new \ReflectionMethod($builder, 'addFeesCollected'))->invoke($builder, $document, $root, $fees, $map);
      $xpath = new \DOMXPath($document);
      $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
      $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
      $fields = [
        'FullTimeEmployeeQuantity',
        'EquivalentFullTimeEmployeeQuantity',
        'TotalFullTimeStaffQuantity',
        'ProcessingCostAmount',
        'LitigationCostAmount',
        'TotalCostAmount',
      ];
      $expected = [
        1 => ['1.23', '2.35', '3.58', '10.01', '20', '30.01'],
        0 => ['2.47', '4.69', '7.16', '20.01', '40.01', '60.02'],
      ];
      foreach ($expected as $id => $values) {
        foreach ($fields as $index => $field) {
          $this->assertSame($values[$index], $xpath->evaluate('string(//foia:PersonnelAndCost[@s:id="PC' . $id . '"]/foia:' . $field . ')'));
        }
      }
      foreach ([1 => '3.01', 0 => '6.01'] as $id => $amount) {
        $base = '//foia:FeesCollected[@s:id="FC' . $id . '"]';
        $this->assertSame($amount, $xpath->evaluate('string(' . $base . '/foia:FeesCollectedAmount)'));
        $this->assertSame('0.1001', $xpath->evaluate('string(' . $base . '/foia:FeesCollectedCostPercent)'));
      }
      $zero = (new FeesCollectedAggregator())->aggregate([
        'components' => [],
        'overall' => ['FeesCollectedAmount' => '0', 'TotalCostAmount' => '0'],
      ]);
      $this->assertSame(['amount' => '0', 'ratio' => '0.0000'], $zero['overall']);
    }
    finally {
      unlink($path);
    }
  }

}
