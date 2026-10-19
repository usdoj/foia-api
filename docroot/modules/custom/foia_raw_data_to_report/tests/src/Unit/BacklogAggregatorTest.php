<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\BacklogAggregator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks only open items exceeding their working-day allowance are backlogged.
 */
#[CoversClass(BacklogAggregator::class)]
#[Group('foia_raw_data_to_report')]
class BacklogAggregatorTest extends UnitTestCase {

  /**
   * Excludes closed items and preserves thresholds regardless of J.
   */
  public function testOpenItemsOnly(): void {
    $paths = [];
    try {
      $sources = [];
      foreach ([1 => FALSE, 2 => TRUE] as $id => $closed_only) {
        $path = tempnam(sys_get_temp_dir(), 'backlog-');
        $paths[] = $path;
        $stream = fopen($path, 'wb');
        fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
        $examples = [
          ['20', '08/29/2025', '', TRUE],
          ['20', '08/01/2025', '', FALSE],
          ['20', '09/02/2025', '', FALSE],
          ['30', '08/15/2025', '  ', TRUE],
          ['30', '08/18/2025', '', FALSE],
          ['20', '08/01/2025', '09/30/2025', FALSE],
          ['', '08/01/2025', '', FALSE],
        ];
        foreach (['request', 'appeal'] as $kind) {
          foreach ($examples as [$allowed, $start, $end, $fallback]) {
            if ($closed_only && trim($end) === '') {
              continue;
            }
            $row = array_fill(0, 29, '');
            $row[3] = $allowed;
            if ($kind === 'request') {
              $row[8] = $start;
              $row[9] = $fallback ? '' : '09/30/2025';
              $row[10] = $end;
            }
            else {
              $row[23] = $start;
              $row[24] = $end;
            }
            fputcsv($stream, $row, ',', '"', '');
          }
        }
        fclose($stream);
        $sources[] = ['component_id' => $id, 'uri' => $path];
      }
      $statistics = (new BacklogAggregator())->aggregate($sources, 2025);
      $this->assertSame(['requests' => 3, 'appeals' => 3], $statistics['components'][1]);
      $this->assertSame(['requests' => 0, 'appeals' => 0], $statistics['components'][2]);
      $this->assertSame(['requests' => 3, 'appeals' => 3], $statistics['overall']);
    }
    finally {
      foreach ($paths as $path) {
        unlink($path);
      }
    }
  }

}
