<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\ExpeditedProcessingAggregator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks appeal rows are excluded from every expedited processing counter.
 */
#[CoversClass(ExpeditedProcessingAggregator::class)]
#[Group('foia_raw_data_to_report')]
class ExpeditedProcessingAggregatorTest extends UnitTestCase {

  /**
   * Excludes appeal outcomes and intervals while retaining initial requests.
   */
  public function testAppealsExcluded(): void {
    $paths = [];
    try {
      $sources = [];
      foreach ([1 => ['', '  ', ' 09/03/2025 '], 2 => ['09/03/2025']] as $id => $appealDates) {
        $path = tempnam(sys_get_temp_dir(), 'expedited-counts-');
        $paths[] = $path;
        $stream = fopen($path, 'wb');
        fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
        foreach ($appealDates as $appeal) {
          foreach ([['G', '09/05/2025'], ['D', '09/30/2025'], ['G', '']] as [$outcome, $determined]) {
            $row = array_fill(0, 29, '');
            $row[16] = '09/03/2025';
            $row[17] = $determined;
            $row[18] = $outcome;
            $row[23] = $appeal;
            fputcsv($stream, $row, ',', '"', '');
          }
        }
        fclose($stream);
        $sources[] = ['component_id' => $id, 'uri' => $path];
      }
      $statistics = (new ExpeditedProcessingAggregator())->aggregate($sources, 2025);
      $expected = ['granted' => 4, 'denied' => 2, 'within_ten' => 2, 'average' => 40 / 3, 'median' => 19];
      $this->assertSame($expected, $statistics['components'][1]);
      $this->assertSame(['granted' => 0, 'denied' => 0, 'within_ten' => 0, 'average' => NULL, 'median' => NULL], $statistics['components'][2]);
      $this->assertSame($expected, $statistics['overall']);
    }
    finally {
      foreach ($paths as $path) {
        unlink($path);
      }
    }
  }

}
