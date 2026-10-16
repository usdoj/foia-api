<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks zero and nonzero appeal response-time average formatting.
 */
#[Group('foia_raw_data_to_report')]
class AppealResponseTimeXmlTest extends UnitTestCase {

  /**
   * Emits zero without decimals and nonzero averages with two decimal places.
   */
  public function testAverageFormatting(): void {
    foreach ([0 => '0', 1 => '1.00', 2 => '1.25'] as $id => $expected) {
      $average = [0, 1, 1.25][$id];
      $values = ['median' => 0, 'average' => $average, 'lowest' => 0, 'highest' => 2];
      $statistics = ['components' => [1 => $values], 'overall' => $values];
      $document = new \DOMDocument();
      $root = $document->createElement('report');
      $document->appendChild($root);
      $builder = new XmlReportBuilder();
      $map = [1 => 'ORG1'];
      (new \ReflectionMethod($builder, 'addAppealResponseTimes'))->invoke($builder, $document, $root, $statistics, $map);
      $xpath = new \DOMXPath($document);
      $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
      $this->assertSame(2, $xpath->query('//foia:ResponseTime')->length);
      foreach ($xpath->query('//foia:ResponseTime') as $entry) {
        $this->assertSame($expected, $xpath->evaluate('string(foia:ResponseTimeAverageDaysValue)', $entry));
        $this->assertSame('0', $xpath->evaluate('string(foia:ResponseTimeMedianDaysValue)', $entry));
        $this->assertSame('0', $xpath->evaluate('string(foia:ResponseTimeLowestDaysValue)', $entry));
        $this->assertSame('2', $xpath->evaluate('string(foia:ResponseTimeHighestDaysValue)', $entry));
      }
    }
  }

}
