<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\AppealNonExemptionDenialAggregator;
use Drupal\foia_raw_data_to_report\AppliedExemptionsAggregator;
use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks empty appeal components and their associations are omitted together.
 */
#[Group('foia_raw_data_to_report')]
class AppealComponentVisibilityTest extends UnitTestCase {

  /**
   * Filters each of the seven sections while preserving agency entries and IDs.
   */
  public function testAppealSections(): void {
    $counts = ['pending_start' => 0, 'received' => 0, 'processed' => 0, 'pending_end' => 0];
    $dispositions = ['affirmed' => 0, 'partial' => 0, 'reversed' => 0, 'other' => 0];
    $exemptions = array_fill_keys(array_keys(AppliedExemptionsAggregator::EXEMPTIONS), 0);
    $denials = array_fill_keys(array_values(AppealNonExemptionDenialAggregator::REASONS), 0);
    $times = ['median' => NULL, 'average' => NULL, 'lowest' => NULL, 'highest' => NULL];
    $cases = [
      [
        'addProcessingStatistics',
        'ProcessedAppealSection',
        'PA',
        $counts,
        array_replace($counts, ['received' => 1]),
      ],
      [
        'addAppealDispositions',
        'AppealDispositionSection',
        'AD',
        $dispositions,
        array_replace($dispositions, ['affirmed' => 1]),
      ],
      [
        'addAppliedExemptions',
        'AppealDispositionAppliedExemptionsSection',
        'ADE',
        $exemptions,
        array_replace($exemptions, [6 => 1]),
      ],
      [
        'addAppealNonExemptionDenials',
        'AppealNonExemptionDenialSection',
        'ANE',
        $denials,
        array_replace($denials, ['Other' => 1]),
      ],
      [
        'addOtherDenialReasons',
        'AppealDenialOtherReasonSection',
        'ADOR',
        [],
        [['description' => 'Reason', 'quantity' => 1]],
      ],
      [
        'addAppealResponseTimes',
        'AppealResponseTimeSection',
        'ART',
        $times,
        array_fill_keys(array_keys($times), 5),
      ],
      [
        'addOldestPendingItems',
        'OldestPendingAppealSection',
        'OPA',
        [],
        [['receipt_date' => '2025-09-30', 'pending_days' => 0]],
      ],
    ];
    foreach ($cases as [$method, $section, $prefix, $empty, $nonempty]) {
      foreach ([FALSE, TRUE] as $all_empty) {
        $active = $all_empty ? $empty : $nonempty;
        $statistics = ['components' => [10 => $empty, 20 => $active], 'overall' => $active];
        $document = new \DOMDocument();
        $root = $document->createElement('report');
        $document->appendChild($root);
        $builder = new XmlReportBuilder();
        $map = [10 => 'ORG1', 20 => 'ORG2'];
        if ($method === 'addAppealResponseTimes') {
          $statistics['components'][30] = array_fill_keys(array_keys($times), 0);
          $map[30] = 'ORG3';
        }
        $arguments = [$builder, $document, $root, $statistics, $map];
        $shared_methods = [
          'addProcessingStatistics',
          'addAppliedExemptions',
          'addOtherDenialReasons',
          'addOldestPendingItems',
        ];
        if (in_array($method, $shared_methods, TRUE)) {
          $arguments[] = $section;
          $arguments[] = $prefix;
        }
        (new \ReflectionMethod($builder, $method))->invoke(...$arguments);
        $xpath = new \DOMXPath($document);
        $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
        $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
        $xpath->registerNamespace('nc', 'http://niem.gov/niem/niem-core/2.0');
        $base = '//foia:' . $section;
        $this->assertSame(0, $xpath->query($base . '/*/nc:OrganizationReference[@s:ref="ORG3"]')->length);
        $this->assertSame($all_empty ? 1 : 2, $xpath->query($base . '/*[@s:id]')->length, $section);
        $this->assertSame(0, $xpath->query($base . '/*[@s:id="' . $prefix . '1"]')->length);
        $this->assertSame(1, $xpath->query($base . '/*[@s:id="' . $prefix . '0"]')->length);
        $this->assertSame(0, $xpath->query($base . '/*/foia:ComponentDataReference[@s:ref="' . $prefix . '1"]')->length);
        $this->assertSame(0, $xpath->query($base . '/*/nc:OrganizationReference[@s:ref="ORG1"]')->length);
        $this->assertSame($all_empty ? 0 : 1, $xpath->query($base . '/*/nc:OrganizationReference[@s:ref="ORG2"]')->length);
        foreach ($xpath->query($base . '/*/foia:ComponentDataReference/@s:ref') as $reference) {
          $this->assertSame(1, $xpath->query($base . '/*[@s:id="' . $reference->value . '"]')->length);
        }
      }
    }
  }

}
