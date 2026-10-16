<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\AppealResponseTimeAggregator;
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

  /**
   * Distinguishes absent appeals from same-day appeals in agency summaries.
   */
  public function testMissingAppeals(): void {
    $paths = [];
    try {
      $sources = [];
      foreach ([1, 2, 3] as $id) {
        $path = tempnam(sys_get_temp_dir(), 'appeal-response-');
        $paths[] = $path;
        $stream = fopen($path, 'wb');
        fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
        $row = array_fill(0, 29, '');
        if ($id === 1) {
          $row[8] = '09/01/2025';
        }
        else {
          $row[23] = '09/30/2025';
          $row[24] = $id === 2 ? '09/30/2025' : '';
        }
        fputcsv($stream, $row, ',', '"', '');
        fclose($stream);
        $sources[] = ['component_id' => $id, 'uri' => $path];
      }
      $aggregator = new AppealResponseTimeAggregator();
      $empty = array_fill_keys(['median', 'average', 'lowest', 'highest'], NULL);
      $all = $aggregator->aggregate($sources, 2025);
      $this->assertSame($empty, $all['components'][1]);
      $this->assertEquals(['median' => 0, 'average' => 0, 'lowest' => 0, 'highest' => 0], $all['overall']);
      foreach ([$sources, [$sources[0]], []] as $selected) {
        $statistics = $aggregator->aggregate($selected, 2025);
        $map = [];
        foreach ($selected as $source) {
          $map[$source['component_id']] = 'ORG' . $source['component_id'];
        }
        $document = new \DOMDocument();
        $root = $document->createElement('report');
        $document->appendChild($root);
        $builder = new XmlReportBuilder();
        (new \ReflectionMethod($builder, 'addAppealResponseTimes'))->invoke($builder, $document, $root, $statistics, $map);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
        $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
        foreach ($xpath->query('//foia:ResponseTime') as $entry) {
          $id = $entry->getAttributeNS('http://niem.gov/niem/structures/2.0', 'id');
          $expected = count($selected) < 2 || $id === 'ART1' ? 'N/A' : '0';
          foreach (['Median', 'Average', 'Lowest', 'Highest'] as $field) {
            $this->assertSame($expected, $xpath->evaluate('string(foia:ResponseTime' . $field . 'DaysValue)', $entry));
          }
        }
      }
    }
    finally {
      foreach ($paths as $path) {
        unlink($path);
      }
    }
  }

}
