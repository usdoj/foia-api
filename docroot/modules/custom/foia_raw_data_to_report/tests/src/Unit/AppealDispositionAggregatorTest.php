<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\AppealDispositionAggregator;
use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks numeric appeal dispositions preserve XML counter mappings.
 */
#[Group('foia_raw_data_to_report')]
class AppealDispositionAggregatorTest extends UnitTestCase {

  /**
   * Maps all four codes to existing component and agency XML quantities.
   */
  public function testNumericCodes(): void {
    $path = tempnam(sys_get_temp_dir(), 'appeal-disposition-');
    try {
      $stream = fopen($path, 'wb');
      fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
      foreach ([' 1 ', '2', '2', '3', '4', '', '  '] as $code) {
        $row = array_fill(0, 29, '');
        $row[25] = $code;
        fputcsv($stream, $row, ',', '"', '');
      }
      fclose($stream);
      $statistics = (new AppealDispositionAggregator())->aggregate([
        ['component_id' => 1, 'uri' => $path],
        ['component_id' => 2, 'uri' => $path],
      ]);
      $this->assertSame(['affirmed' => 1, 'partial' => 2, 'reversed' => 1, 'other' => 1], $statistics['components'][1]);
      $this->assertSame(['affirmed' => 2, 'partial' => 4, 'reversed' => 2, 'other' => 2], $statistics['overall']);
      $document = new \DOMDocument();
      $root = $document->createElement('report');
      $document->appendChild($root);
      $builder = new XmlReportBuilder();
      $map = [1 => 'ORG1', 2 => 'ORG2'];
      (new \ReflectionMethod($builder, 'addAppealDispositions'))->invoke($builder, $document, $root, $statistics, $map);
      $xpath = new \DOMXPath($document);
      $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
      $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
      foreach (['Affirmed' => 1, 'Partial' => 2, 'Reversed' => 1, 'Other' => 1, 'Total' => 5] as $field => $quantity) {
        foreach ([0 => 2, 1 => 1, 2 => 1] as $id => $multiplier) {
          $query = '//foia:AppealDisposition[@s:id="AD' . $id . '"]/foia:AppealDisposition' . $field . 'Quantity';
          $this->assertSame((string) ($quantity * $multiplier), $xpath->evaluate('string(' . $query . ')'));
        }
      }
    }
    finally {
      unlink($path);
    }
  }

}
