<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Calculates working-day adjudication intervals and weighted summaries.
 */
final class AdjudicationStatistics {

  /**
   * Validates dates before counting elapsed working days.
   */
  public static function days(string $start, string $end): int {
    $dates = [];
    foreach ([$start, $end] as $value) {
      if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $value, $parts)
        || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[3])) {
        throw new \RuntimeException('Adjudication requires valid start and end dates in MM/DD/YYYY format.');
      }
      $dates[] = (int) $parts[3] * 10000 + (int) $parts[1] * 100 + (int) $parts[2];
    }
    if ($dates[1] < $dates[0]) {
      throw new \RuntimeException('Adjudication completion cannot precede its start date.');
    }
    return (new WorkingDays())->count($start, $end);
  }

  /**
   * Returns average and median from frequencies, or NULL for no outcomes.
   */
  public static function summarize(array $histogram): array {
    if ($histogram === []) {
      return ['average' => NULL, 'median' => NULL];
    }
    ksort($histogram, SORT_NUMERIC);
    $count = array_sum($histogram);
    $lower_position = intdiv($count + 1, 2);
    $upper_position = intdiv($count, 2) + 1;
    $seen = $sum = 0;
    $lower = $upper = NULL;
    foreach ($histogram as $days => $frequency) {
      $seen += $frequency;
      $sum += $days * $frequency;
      if ($lower === NULL && $seen >= $lower_position) {
        $lower = $days;
      }
      if ($upper === NULL && $seen >= $upper_position) {
        $upper = $days;
      }
    }
    return ['average' => $sum / $count, 'median' => ($lower + $upper) / 2];
  }

}
