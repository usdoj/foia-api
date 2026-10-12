<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\StatuteAggregator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests embedded statute details and custom statute isolation.
 */
#[CoversClass(StatuteAggregator::class)]
#[Group('foia_raw_data_to_report')]
class StatuteAggregatorTest extends UnitTestCase {

  /**
   * Mixed codes use embedded details without leaking custom text to them.
   */
  public function testMixedStatutes(): void {
    $path = tempnam(sys_get_temp_dir(), 'foia-statutes-');
    try {
      $stream = fopen($path, 'wb');
      fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
      foreach ([
        ['1,1,77', ' Custom statute ', ' Custom withheld ', ' Custom citation '],
        ['1,77', 'Custom statute', 'Second withheld', 'Second citation'],
        ['7,77', 'Different statute', 'Different withheld', 'Different citation'],
      ] as $values) {
        $row = array_fill(0, 29, '');
        foreach ($values as $offset => $value) {
          $row[4 + $offset] = $value;
        }
        fputcsv($stream, $row, ',', '"', '');
      }
      fclose($stream);
      $statutes = (new StatuteAggregator())->aggregate([
        ['component_id' => 10, 'uri' => $path],
      ]);
      $this->assertCount(4, $statutes);
      $this->assertSame(StatuteAggregator::STATUTES[1]['description'], $statutes[1]['description']);
      $this->assertSame([StatuteAggregator::STATUTES[1]['information_withheld']], array_values($statutes[1]['information_withheld']));
      $this->assertSame([StatuteAggregator::STATUTES[1]['citation']], array_values($statutes[1]['citations']));
      $this->assertSame([10 => 2], $statutes[1]['counts']);
      $this->assertSame([], $statutes[7]['information_withheld']);
      $this->assertSame([], $statutes[7]['citations']);
      $custom = $statutes['77:Custom statute'];
      $this->assertSame('Custom statute', $custom['description']);
      $this->assertSame(['Custom withheld', 'Second withheld'], array_values($custom['information_withheld']));
      $this->assertSame(['Custom citation', 'Second citation'], array_values($custom['citations']));
      $this->assertSame([10 => 2], $custom['counts']);
      $this->assertSame([10 => 1], $statutes['77:Different statute']['counts']);
    }
    finally {
      unlink($path);
    }
  }

}
