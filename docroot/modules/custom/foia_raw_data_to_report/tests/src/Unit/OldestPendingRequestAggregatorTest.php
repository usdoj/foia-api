<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\OldestPendingRequestAggregator;
use Drupal\foia_raw_data_to_report\WorkingDays;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks perfected-date selection retains receipt-date XML data.
 */
#[Group('foia_raw_data_to_report')]
class OldestPendingRequestAggregatorTest extends UnitTestCase {

  /**
   * Selects by J, excludes blank J/closed rows, and preserves consultations.
   */
  public function testPerfectedSelection(): void {
    $paths = [];
    try {
      $sources = [];
      foreach ([1, 2] as $id) {
        $path = tempnam(sys_get_temp_dir(), 'oldest-pending-');
        $paths[] = $path;
        $stream = fopen($path, 'wb');
        fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
        for ($day = 1; $day <= 12; $day++) {
          $row = array_fill(0, 29, '');
          $row[2] = 'N';
          // Reverse receipt order so ranking by I would give a different set.
          $row[8] = sprintf('01/%02d/2025', 13 - $day);
          $row[9] = sprintf('02/%02d/2025', $day);
          fputcsv($stream, $row, ',', '"', '');
        }
        foreach (['', '   ', '01/02/2025'] as $perfected) {
          $row = array_fill(0, 29, '');
          $row[2] = $perfected === '' ? 'Y' : 'N';
          $row[8] = '01/01/2025';
          $row[9] = $perfected;
          $row[10] = trim($perfected) === '' ? '' : '03/01/2025';
          fputcsv($stream, $row, ',', '"', '');
        }
        fclose($stream);
        $sources[] = ['component_id' => $id, 'uri' => $path];
      }
      $aggregator = new OldestPendingRequestAggregator();
      $statistics = $aggregator->aggregate($sources, 2025);
      foreach ($statistics['components'] as $items) {
        $this->assertCount(10, $items);
        $this->assertSame('2025-01-12', $items[0]['receipt_date']);
        $this->assertSame('2025-02-01', $items[0]['perfected_date']);
        $this->assertSame('2025-02-10', $items[9]['perfected_date']);
        $this->assertSame((new WorkingDays())->count('01/12/2025', '09/30/2025'), $items[0]['pending_days']);
      }
      $this->assertCount(10, $statistics['overall']);
      $this->assertSame('2025-02-05', $statistics['overall'][9]['perfected_date']);
      $consultations = $aggregator->aggregate($sources, 2025, TRUE);
      $this->assertCount(2, $consultations['overall']);
      $this->assertSame('2025-01-01', $consultations['overall'][0]['receipt_date']);
    }
    finally {
      foreach ($paths as $path) {
        unlink($path);
      }
    }
  }

}
