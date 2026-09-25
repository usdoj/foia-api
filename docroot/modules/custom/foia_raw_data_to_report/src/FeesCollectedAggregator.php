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
   * Divides fees by total cost, without multiplying the ratio by 100.
   */
  private function calculate(array $values): array {
    $amount = BigDecimal::of($values['FeesCollectedAmount']);
    $cost = BigDecimal::of($values['TotalCostAmount']);
    return [
      'amount' => (string) $amount->toScale(4),
      // A zero denominator produces zero, even if fees are nonzero.
      'ratio' => $cost->isZero() ? '0.0000' : (string) $amount->dividedBy($cost, 4, RoundingMode::HalfUp),
    ];
  }

}
