<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks whole-number formatting in pending perfected request XML.
 */
#[Group('foia_raw_data_to_report')]
class PendingPerfectedRequestsXmlTest extends UnitTestCase {

  /**
   * Omits .00 while retaining fractional values, rounding, and empty tracks.
   */
  public function testNumberFormatting(): void {
    $cases = [
      [1, 0, 0, '0', '0'],
      [1, 3, 3, '3', '3'],
      [2, 3.5, 3.5, '3.5', '3.50'],
      [3, 4, 4.126, '4', '4.13'],
      [3, 4, 3.999, '4', '4'],
      [0, NULL, NULL, 'N/A', 'N/A'],
    ];
    foreach ($cases as [$quantity, $median, $average, $expected_median, $expected_average]) {
      $values = ['quantity' => $quantity, 'median' => $median, 'average' => $average];
      $tracks = array_fill_keys(['S', 'C', 'E'], $values);
      $statistics = ['components' => [1 => $tracks], 'overall' => $tracks];
      $document = new \DOMDocument();
      $root = $document->createElement('report');
      $document->appendChild($root);
      $builder = new XmlReportBuilder();
      $map = [1 => 'ORG1'];
      (new \ReflectionMethod($builder, 'addPendingPerfectedRequests'))->invoke($builder, $document, $root, $statistics, $map);
      $xpath = new \DOMXPath($document);
      $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
      $bins = $xpath->query('//foia:PendingPerfectedRequests/*');
      $this->assertSame(6, $bins->length);
      foreach ($bins as $bin) {
        $this->assertSame((string) $quantity, $xpath->evaluate('string(foia:PendingRequestQuantity)', $bin));
        $this->assertSame($expected_median, $xpath->evaluate('string(foia:PendingRequestMedianDaysValue)', $bin));
        $this->assertSame($expected_average, $xpath->evaluate('string(foia:PendingRequestAverageDaysValue)', $bin));
      }
    }
  }

}
