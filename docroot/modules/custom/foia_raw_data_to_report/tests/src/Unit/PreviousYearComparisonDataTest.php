<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\foia_raw_data_to_report\PreviousYearComparisonData;
use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks optional previous-year lookups and all comparison XML mappings.
 */
#[Group('foia_raw_data_to_report')]
class PreviousYearComparisonDataTest extends UnitTestCase {

  /**
   * Creates a fieldable entity with scalar fields and paragraph references.
   */
  private function entity(array $values): FieldableEntityInterface {
    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('hasField')->willReturnCallback(static fn($name) => array_key_exists($name, $values));
    $entity->method('get')->willReturnCallback(function ($name) use ($values) {
      $field = $this->createMock(FieldItemList::class);
      $value = $values[$name];
      $field->method('__get')->willReturnCallback(static fn($property) => $value);
      $items = is_array($value) ? array_map(static fn($paragraph) => (object) ['entity' => $paragraph], $value) : [];
      $field->method('getIterator')->willReturn(new \ArrayIterator($items));
      return $field;
    });
    return $entity;
  }

  /**
   * Creates a reader with an expected agency/year lookup.
   */
  private function reader(array $ids, ?FieldableEntityInterface $report = NULL, bool $fail = FALSE): PreviousYearComparisonData {
    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->with(FALSE)->willReturnSelf();
    $conditions = [];
    $query->method('condition')->willReturnCallback(function ($field, $value) use (&$conditions, $query) {
      $conditions[$field] = $value;
      return $query;
    });
    $query->method('sort')->willReturnSelf();
    $query->method('range')->with(0, 2)->willReturnSelf();
    $query->method('execute')->willReturnCallback(function () use ($ids, &$conditions) {
      $this->assertSame([
        'type' => 'annual_foia_report_data',
        'field_agency.target_id' => 7,
        'field_foia_annual_report_yr' => 2025,
      ], $conditions);
      return $ids;
    });
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);
    $storage->method('load')->willReturn($report);
    $manager = $this->createMock(EntityTypeManagerInterface::class);
    if ($fail) {
      $manager->method('getStorage')->willThrowException(new \RuntimeException('Lookup unavailable.'));
    }
    else {
      $manager->method('getStorage')->with('node')->willReturn($storage);
    }
    return new PreviousYearComparisonData($manager);
  }

  /**
   * Reads six fields by component ID and uses independent agency totals.
   */
  public function testReportValuesAndXml(): void {
    $report = $this->entity([
      'field_foia_requests_va' => [
        $this->entity([
          'field_agency_component' => 10,
          'field_req_received_yr' => '11',
          'field_req_processed_yr' => '12',
        ]),
        $this->entity([
          'field_agency_component' => 99,
          'field_req_received_yr' => '999',
          'field_req_processed_yr' => '999',
        ]),
      ],
      'field_admin_app_via' => [
        $this->entity([
          'field_agency_component' => 10,
          'field_app_received_yr' => '21',
          'field_app_processed_yr' => '22',
        ]),
      ],
      'field_foia_xiia' => [
        $this->entity(['field_agency_component' => 10, 'field_back_req_end_yr' => '31', 'field_back_app_end_yr' => '32']),
      ],
      'field_overall_req_received_yr' => '101',
      'field_overall_req_processed_yr' => '102',
      'field_overall_via_app_recd_yr' => '201',
      'field_overall_via_app_proc_yr' => '202',
      'field_overall_xiia_back_req_end_' => '301',
      'field_overall_xiia_back_app_end_' => '302',
    ]);
    $previous = $this->reader([123], $report)->load(7, 2026, [10, 20]);
    $this->assertCount(3, $previous['notes'], implode("\n", $previous['notes']));
    $document = new \DOMDocument();
    $root = $document->createElement('report');
    $document->appendChild($root);
    $builder = new XmlReportBuilder();
    $map = [10 => 'ORG1', 20 => 'ORG2'];
    foreach (['requests' => 'PRC', 'appeals' => 'APC'] as $kind => $prefix) {
      $counts = ['received' => 50, 'processed' => 40];
      $statistics = [
        'components' => [10 => $counts, 20 => $counts],
        'overall' => $counts,
        'previous' => $previous[$kind],
      ];
      (new \ReflectionMethod($builder, 'addProcessingComparison'))->invoke($builder, $document, $root, $statistics, $map, $prefix, $prefix);
    }
    foreach (['requests' => 'BLR', 'appeals' => 'ABC'] as $kind => $prefix) {
      $counts = ['requests' => 3, 'appeals' => 4];
      $statistics = [
        'components' => [10 => $counts, 20 => $counts],
        'overall' => $counts,
        'previous' => $previous['backlog'],
      ];
      (new \ReflectionMethod($builder, 'addBacklogComparison'))->invoke($builder, $document, $root, $statistics, $map, $prefix, $prefix, $kind);
    }
    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
    $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
    $expected = ['PRC' => [11, 12, 101, 102], 'APC' => [21, 22, 201, 202]];
    foreach ($expected as $prefix => [$received, $processed, $overall_received, $overall_processed]) {
      $organizations = [
        0 => [$overall_received, $overall_processed],
        1 => [$received, $processed],
        2 => [0, 0],
      ];
      foreach ($organizations as $id => [$r, $p]) {
        $base = '//foia:ProcessingComparison[@s:id="' . $prefix . $id . '"]';
        $this->assertSame((string) $r, $xpath->evaluate('string(' . $base . '/foia:ItemsReceivedLastYearQuantity)'));
        $this->assertSame((string) $p, $xpath->evaluate('string(' . $base . '/foia:ItemsProcessedLastYearQuantity)'));
        $this->assertSame('50', $xpath->evaluate('string(' . $base . '/foia:ItemsReceivedCurrentYearQuantity)'));
      }
    }
    foreach (['BLR' => [301, 31, 0], 'ABC' => [302, 32, 0]] as $prefix => $values) {
      foreach ($values as $id => $value) {
        $base = '//foia:BacklogComparison[@s:id="' . $prefix . $id . '"]';
        $this->assertSame((string) $value, $xpath->evaluate('string(' . $base . '/foia:BacklogLastYearQuantity)'));
      }
    }
  }

  /**
   * Missing, ambiguous, unavailable, or incomplete data leaves zero defaults.
   */
  public function testFallbacks(): void {
    foreach ([[], [1, 2]] as $ids) {
      $result = $this->reader($ids)->load(7, 2026, [10]);
      $this->assertNotEmpty($result['notes']);
      $this->assertSame(['received' => '0', 'processed' => '0'], $result['requests']['components'][10]);
      $this->assertSame(['requests' => '0', 'appeals' => '0'], $result['backlog']['overall']);
    }
    $result = $this->reader([], NULL, TRUE)->load(7, 2026, [10]);
    $this->assertNotEmpty($result['notes']);
    $this->assertSame('0', $result['requests']['overall']['received']);
    $report = $this->entity([
      'field_overall_req_received_yr' => 'invalid',
      'field_overall_req_processed_yr' => '000123',
      'field_foia_requests_va' => [
        $this->entity(['field_agency_component' => 10, 'field_req_received_yr' => '']),
      ],
    ]);
    $result = $this->reader([123], $report)->load(7, 2026, [10]);
    $this->assertSame('0', $result['requests']['overall']['received']);
    $this->assertSame('123', $result['requests']['overall']['processed'], implode("\n", $result['notes']));
    $this->assertSame('0', $result['requests']['components'][10]['processed']);
    $this->assertNotEmpty($result['notes']);
  }

}
