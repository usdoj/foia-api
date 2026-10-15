<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\AppealNonExemptionDenialAggregator;
use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks numeric appeal denial reasons retain existing XML labels and counts.
 */
#[Group('foia_raw_data_to_report')]
class AppealNonExemptionDenialAggregatorTest extends UnitTestCase {

  /**
   * Counts each distinct code once and sums component counts for agency XML.
   */
  public function testNumericReasons(): void {
    $path = tempnam(sys_get_temp_dir(), 'appeal-reasons-');
    try {
      $stream = fopen($path, 'wb');
      fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
      foreach (['1,2,3,4,5,6,7,8,9,10,11', ' 1, 1, 11,11 ', ''] as $value) {
        $row = array_fill(0, 29, '');
        $row[26] = $value;
        fputcsv($stream, $row, ',', '"', '');
      }
      fclose($stream);
      $statistics = (new AppealNonExemptionDenialAggregator())->aggregate([
        ['component_id' => 1, 'uri' => $path],
        ['component_id' => 2, 'uri' => $path],
      ]);
      $document = new \DOMDocument();
      $root = $document->createElement('report');
      $document->appendChild($root);
      $builder = new XmlReportBuilder();
      $map = [1 => 'ORG1', 2 => 'ORG2'];
      (new \ReflectionMethod($builder, 'addAppealNonExemptionDenials'))->invoke($builder, $document, $root, $statistics, $map);
      $xpath = new \DOMXPath($document);
      $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
      $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
      $labels = ['NoRecords', 'Referred', 'Withdrawn', 'FeeRelated', 'NotDescribed', 'ImproperRequest',
        'NotAgency', 'Duplicate', 'InLitigation', 'ExpeditedDenial', 'Other',
      ];
      foreach ($labels as $label) {
        $count = in_array($label, ['NoRecords', 'Other'], TRUE) ? 2 : 1;
        $this->assertSame($count, $statistics['components'][1][$label]);
        $this->assertSame($count * 2, $statistics['overall'][$label]);
        $query = '//foia:AppealNonExemptionDenial[@s:id="ANE0"]/foia:NonExemptionDenial[foia:NonExemptionDenialReasonCode="' . $label . '"]/foia:NonExemptionDenialQuantity';
        $this->assertSame((string) ($count * 2), $xpath->evaluate('string(' . $query . ')'));
      }
    }
    finally {
      unlink($path);
    }
  }

}
