<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\ProcessedResponseTimeAggregator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests perfected and completed date requirements for response time sections.
 */
#[CoversClass(ProcessedResponseTimeAggregator::class)]
#[Group('foia_raw_data_to_report')]
class ProcessedResponseTimeAggregatorTest extends UnitTestCase {

  /**
   * All five outputs skip blank J/K and use J even when I is earlier.
   */
  public function testRequiredDates(): void {
    $path = tempnam(sys_get_temp_dir(), 'response-times-');
    try {
      $stream = fopen($path, 'wb');
      fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
      foreach (['S', 'C', 'E'] as $track) {
        foreach ([
          ['09/03/2025', '09/05/2025', '1'],
          ['09/03/2025', '09/09/2025', '3'],
          ['09/03/2025', '09/09/2025', ''],
          ['', '09/30/2025', '1'],
          ['   ', '09/30/2025', '2'],
          ['09/03/2025', '', '1'],
        ] as [$perfected, $completed, $disposition]) {
          $row = array_fill(0, 29, '');
          $row[8] = '08/01/2025';
          $row[9] = $perfected;
          $row[10] = $completed;
          $row[12] = $track;
          $row[13] = $disposition;
          fputcsv($stream, $row, ',', '"', '');
        }
      }
      fclose($stream);
      $sources = [['component_id' => 1, 'uri' => $path]];
      $aggregator = new ProcessedResponseTimeAggregator();
      $processed = $aggregator->aggregate($sources);
      $granted = $aggregator->aggregate($sources, TRUE);
      foreach (['S', 'C', 'E'] as $track) {
        foreach ([$processed['components'][1], $processed['overall']] as $values) {
          $this->assertEquals(['median' => 4, 'average' => 10 / 3, 'lowest' => 2, 'highest' => 4], $values[$track]);
        }
        foreach ([$granted['components'][1], $granted['overall']] as $values) {
          $this->assertEquals(['median' => 2, 'average' => 2, 'lowest' => 2, 'highest' => 2], $values[$track]);
        }
      }
      foreach (['aggregateSimpleIncrements', 'aggregateComplexIncrements', 'aggregateExpeditedIncrements'] as $method) {
        $increments = $aggregator->$method($sources);
        $this->assertSame(3, $increments['components'][1]['1-20']);
        $this->assertSame(3, array_sum($increments['components'][1]));
        $this->assertSame($increments['components'][1], $increments['overall']);
      }
    }
    finally {
      unlink($path);
    }
  }

}
