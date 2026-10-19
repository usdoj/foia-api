<?php

namespace Drupal\foia_raw_data_to_report;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Formats Section IX-XI fees and their ratios to personnel/cost totals.
 */
final class FeesCollectedAggregator {

  /**
   * Uses exact component/agency totals from PersonnelAndCostAggregator.
   */
  public function aggregate(array $statistics): array {
    $components = [];
    foreach ($statistics['components'] as $id => $values) {
      $components[$id] = $this->calculate($values);
    }
    return [
      'components' => $components,
      'overall' => $this->calculate($statistics['overall']),
    ];
  }

  /**
   * Calculates fees as a percentage of total cost.
   */
  private function calculate(array $values): array {
    $amount = BigDecimal::of($values['FeesCollectedAmount']);
    $cost = BigDecimal::of($values['TotalCostAmount']);
    return [
      'amount' => (string) $amount->toScale(2, RoundingMode::HalfUp)->strippedOfTrailingZeros(),
      // A zero denominator produces zero, even if fees are nonzero.
      'ratio' => $cost->isZero() ? '0.00' : (string) $amount->multipliedBy(100)->dividedBy($cost, 2, RoundingMode::HalfUp),
    ];
  }

}
