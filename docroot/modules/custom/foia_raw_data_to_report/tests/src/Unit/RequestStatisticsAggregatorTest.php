<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\ConsultationStatisticsAggregator;
use Drupal\foia_raw_data_to_report\DispositionAggregator;
use Drupal\foia_raw_data_to_report\RequestStatisticsAggregator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks initial request statistics exclude consultations from every counter.
 */
#[CoversClass(RequestStatisticsAggregator::class)]
#[Group('foia_raw_data_to_report')]
class RequestStatisticsAggregatorTest extends UnitTestCase {

  /**
   * Separates consultations and reconciles dispositions at fiscal boundaries.
   */
  public function testConsultationsExcluded(): void {
    $paths = [];
    try {
      $sources = [];
      foreach ([1 => ['N', ' Y '], 2 => ['Y']] as $id => $types) {
        $path = tempnam(sys_get_temp_dir(), 'request-statistics-');
        $paths[] = $path;
        $stream = fopen($path, 'wb');
        fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
        foreach ($types as $type) {
          foreach ([
            ['09/30/2024', '10/01/2024'],
            ['10/01/2024', '09/30/2025'],
            ['09/30/2024', ''],
            ['09/30/2025', ''],
          ] as [$received, $completed]) {
            $row = array_fill(0, 29, '');
            $row[2] = $type;
            $row[8] = $received;
            $row[10] = $completed;
            $row[13] = $type === 'N' && $completed !== '' ? '6' : '';
            fputcsv($stream, $row, ',', '"', '');
          }
        }
        fclose($stream);
        $sources[] = ['component_id' => $id, 'uri' => $path];
      }
      $requests = (new RequestStatisticsAggregator())->aggregate($sources, 2025);
      $expected = ['pending_start' => 2, 'received' => 2, 'processed' => 2, 'pending_end' => 2];
      $this->assertSame($expected, $requests['components'][1]);
      $this->assertSame(array_fill_keys(array_keys($expected), 0), $requests['components'][2]);
      $this->assertSame($expected, $requests['overall']);
      $dispositions = (new DispositionAggregator())->aggregate($sources);
      foreach ([1, 2] as $id) {
        $this->assertSame(array_sum($dispositions['components'][$id]), $requests['components'][$id]['processed']);
      }
      $this->assertSame(array_sum($dispositions['overall']), $requests['overall']['processed']);
      $consultations = (new ConsultationStatisticsAggregator())->aggregate($sources, 2025);
      $this->assertSame($expected, $consultations['components'][1]);
      $this->assertSame($expected, $consultations['components'][2]);
      $this->assertSame(['pending_start' => 4, 'received' => 4, 'processed' => 4, 'pending_end' => 4], $consultations['overall']);
    }
    finally {
      foreach ($paths as $path) {
        unlink($path);
      }
    }
  }

}
