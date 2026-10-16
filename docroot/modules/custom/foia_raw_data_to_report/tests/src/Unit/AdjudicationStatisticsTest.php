<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\AdjudicationStatistics;
use Drupal\foia_raw_data_to_report\ExpeditedProcessingAggregator;
use Drupal\foia_raw_data_to_report\FeeWaiverAggregator;
use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks calendar-day expedited and working-day fee-waiver summaries.
 */
#[Group('foia_raw_data_to_report')]
class AdjudicationStatisticsTest extends UnitTestCase {

  /**
   * Uses fiscal year-end for missing completions and combines frequencies.
   */
  public function testSummariesAndXml(): void {
    $paths = [];
    try {
      $sources = [];
      foreach ([1 => ['09/05/2025', ''], 2 => ['09/09/2025'], 3 => []] as $id => $ends) {
        $path = tempnam(sys_get_temp_dir(), 'adjudication-');
        $paths[] = $path;
        $stream = fopen($path, 'wb');
        fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
        foreach ($ends as $end) {
          $row = array_fill(0, 29, '');
          $row[16] = $row[19] = '09/03/2025';
          $row[17] = $row[20] = $end;
          $row[18] = $row[21] = $end === '' ? 'D' : 'G';
          fputcsv($stream, $row, ',', '"', '');
        }
        fclose($stream);
        $sources[] = ['component_id' => $id, 'uri' => $path];
      }
      foreach ([
        [new ExpeditedProcessingAggregator(), 'addExpeditedProcessing', 'ExpeditedProcessing', 'EP'],
        [new FeeWaiverAggregator(), 'addFeeWaivers', 'FeeWaiver', 'FW'],
      ] as [$aggregator, $method, $element, $prefix]) {
        $statistics = $aggregator->aggregate($sources, 2025);
        $expedited = $aggregator instanceof ExpeditedProcessingAggregator;
        $this->assertSame($expedited ? 14.5 : 10.5, $statistics['components'][1]['average']);
        $this->assertSame($expedited ? 14.5 : 10.5, $statistics['components'][1]['median']);
        $this->assertSame($expedited ? 35 / 3 : 25 / 3, $statistics['overall']['average']);
        $this->assertEquals($expedited ? 6 : 4, $statistics['overall']['median']);
        $document = new \DOMDocument();
        $root = $document->createElement('report');
        $document->appendChild($root);
        $builder = new XmlReportBuilder();
        $map = [1 => 'ORG1', 2 => 'ORG2', 3 => 'ORG3'];
        (new \ReflectionMethod($builder, $method))->invoke($builder, $document, $root, $statistics, $map);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
        $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
        $expected = [
          0 => $expedited ? ['11.67', '6'] : ['8.33', '4'],
          1 => $expedited ? ['14.5', '14.5'] : ['10.5', '10.5'],
          2 => $expedited ? ['6', '6'] : ['4', '4'],
          3 => ['N/A', 'N/A'],
        ];
        foreach ($expected as $id => [$average, $median]) {
          $base = '//foia:' . $element . '[@s:id="' . $prefix . $id . '"]';
          $this->assertSame($average, $xpath->evaluate('string(' . $base . '/foia:AdjudicationAverageDaysValue)'));
          $this->assertSame($median, $xpath->evaluate('string(' . $base . '/foia:AdjudicationMedianDaysValue)'));
        }
      }
    }
    finally {
      foreach ($paths as $path) {
        unlink($path);
      }
    }
  }

  /**
   * Excludes weekends and a listed holiday, and preserves same-day zero.
   */
  public function testWorkingDays(): void {
    $this->assertSame(1, AdjudicationStatistics::days('08/29/2025', '09/02/2025'));
    $this->assertSame(0, AdjudicationStatistics::days('09/30/2025', '09/30/2025'));
  }

  /**
   * Floors expedited intervals before summarizing; fee waivers stay unchanged.
   */
  public function testExpeditedMinimumDay(): void {
    $path = tempnam(sys_get_temp_dir(), 'expedited-minimum-');
    try {
      $stream = fopen($path, 'wb');
      fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
      foreach ([
        ['09/03/2025', '09/03/2025'],
        ['09/03/2025', '09/04/2025'],
        ['09/05/2025', '09/06/2025'],
        ['09/30/2025', ''],
      ] as [$received, $determined]) {
        $row = array_fill(0, 29, '');
        $row[16] = $row[19] = $received;
        $row[17] = $row[20] = $determined;
        $row[18] = $row[21] = 'G';
        fputcsv($stream, $row, ',', '"', '');
      }
      fclose($stream);
      $sources = [['component_id' => 1, 'uri' => $path]];
      $expedited = (new ExpeditedProcessingAggregator())->aggregate($sources, 2025);
      foreach ([$expedited['components'][1], $expedited['overall']] as $statistics) {
        $this->assertEquals(1, $statistics['average']);
        $this->assertEquals(1, $statistics['median']);
        $this->assertSame(4, $statistics['granted']);
        $this->assertSame(3, $statistics['within_ten']);
      }
      $fees = (new FeeWaiverAggregator())->aggregate($sources, 2025);
      $this->assertEquals(0.25, $fees['overall']['average']);
      $this->assertEquals(0, $fees['overall']['median']);
    }
    finally {
      unlink($path);
    }
  }

  /**
   * Ten-day checks include weekends and holidays, with an inclusive threshold.
   */
  public function testCalendarDayThreshold(): void {
    $path = tempnam(sys_get_temp_dir(), 'expedited-calendar-');
    try {
      $stream = fopen($path, 'wb');
      fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
      foreach (['09/08/2025', '09/09/2025'] as $end) {
        $row = array_fill(0, 29, '');
        $row[16] = '08/29/2025';
        $row[17] = $end;
        $row[18] = 'D';
        fputcsv($stream, $row, ',', '"', '');
      }
      fclose($stream);
      $statistics = (new ExpeditedProcessingAggregator())->aggregate([
        ['component_id' => 1, 'uri' => $path],
      ], 2025);
      $this->assertSame(1, $statistics['overall']['within_ten']);
      $this->assertSame(10.5, $statistics['overall']['average']);
      $this->assertSame(10.5, $statistics['overall']['median']);
    }
    finally {
      unlink($path);
    }
  }

}
