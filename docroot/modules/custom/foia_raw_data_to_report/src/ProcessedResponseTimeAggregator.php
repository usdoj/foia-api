<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams completed requests into working-day response times by track.
 */
final class ProcessedResponseTimeAggregator {

  /**
   * Track codes and XML element names, in report order.
   */
  public const TRACKS = [
    'S' => 'SimpleResponseTime',
    'C' => 'ComplexResponseTime',
    'E' => 'ExpeditedResponseTime',
  ];

  /**
   * Returns component and agency summaries without retaining individual rows.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   * @param bool $information_granted_only
   *   Whether to include only disposition codes 1 and 2 in Column N.
   *
   * @return array
   *   Component and overall statistics keyed by track, empty for unused tracks.
   */
  public function aggregate(array $sources, bool $information_granted_only = FALSE): array {
    $histograms = $this->collectHistograms($sources, $information_granted_only);
    $empty = array_fill_keys(array_keys(self::TRACKS), []);
    $components = [];
    $overall = $empty;
    foreach ($histograms as $id => $tracks) {
      foreach ($tracks as $track => $histogram) {
        $components[$id][$track] = $this->summarize($histogram);
        foreach ($histogram as $days => $count) {
          $overall[$track][$days] = ($overall[$track][$days] ?? 0) + $count;
        }
      }
    }
    foreach ($overall as $track => $histogram) {
      $overall[$track] = $this->summarize($histogram);
    }
    return ['components' => $components, 'overall' => $overall];
  }

  /**
   * Upper bounds for the ordered response-time bins; the last is unbounded.
   */
  public const INCREMENTS = [
    '1-20' => 20,
    '21-40' => 40,
    '41-60' => 60,
    '61-80' => 80,
    '81-100' => 100,
    '101-120' => 120,
    '121-140' => 140,
    '141-160' => 160,
    '161-180' => 180,
    '181-200' => 200,
    '201-300' => 300,
    '301-400' => 400,
    '401+' => PHP_INT_MAX,
  ];

  /**
   * Counts simple information-granted requests in all thirteen day ranges.
   */
  public function aggregateSimpleIncrements(array $sources): array {
    return $this->aggregateIncrements($sources, 'S');
  }

  /**
   * Counts complex information-granted requests in all thirteen day ranges.
   */
  public function aggregateComplexIncrements(array $sources): array {
    return $this->aggregateIncrements($sources, 'C');
  }

  /**
   * Counts expedited information-granted requests in all thirteen day ranges.
   */
  public function aggregateExpeditedIncrements(array $sources): array {
    return $this->aggregateIncrements($sources, 'E');
  }

  /**
   * Bins completed information-granted requests for the selected track.
   */
  private function aggregateIncrements(array $sources, string $track): array {
    $histograms = $this->collectHistograms($sources, TRUE, $track);
    $empty = array_fill_keys(array_keys(self::INCREMENTS), 0);
    $components = [];
    $overall = $empty;
    foreach ($histograms as $id => $tracks) {
      $components[$id] = $empty;
      // Same-day completions belong in 1-20 so every selected row counts.
      foreach ($tracks[$track] as $days => $count) {
        foreach (self::INCREMENTS as $code => $upper) {
          if ($days <= $upper) {
            $components[$id][$code] += $count;
            $overall[$code] += $count;
            break;
          }
        }
      }
    }
    return ['components' => $components, 'overall' => $overall];
  }

  /**
   * Shares CSV selection and working-day calculation across both outputs.
   */
  private function collectHistograms(array $sources, bool $information_granted_only, ?string $only_track = NULL): array {
    $empty = array_fill_keys(array_keys(self::TRACKS), []);
    $histograms = [];
    $working_days = new WorkingDays();
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $histograms[$id] ??= $empty;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for processed response times.');
      }
      try {
        $header = TRUE;
        $record = 0;
        while (($columns = fgetcsv($stream, 0, ',', '"', '')) !== FALSE) {
          $record++;
          if ($columns === [NULL]) {
            continue;
          }
          if (count($columns) !== CsvValidator::EXPECTED_COLUMNS) {
            throw new \RuntimeException('CSV column count changed after validation.');
          }
          if ($header) {
            $header = FALSE;
            continue;
          }
          // Information-granted statistics include only full/partial grants.
          if ($information_granted_only && !in_array(trim($columns[13]), ['1', '2'], TRUE)) {
            continue;
          }
          // Only completed requests in one of the three tracks contribute.
          $completed = trim($columns[10]);
          $track = trim($columns[12]);
          if ($completed === '' || !isset(self::TRACKS[$track]) || ($only_track !== NULL && $track !== $only_track)) {
            continue;
          }
          // Prefer J; fall back to I without clamping to the fiscal year.
          $perfected = trim($columns[9]);
          $start = $perfected !== '' ? $perfected : trim($columns[8]);
          $context = sprintf('Component %s, CSV record %d', $id, $record);
          if ($start === '') {
            throw new \RuntimeException($context . ': Completed requests require a date in Column J or I to calculate response time.');
          }
          $start_date = $this->calendarDate($start, $context . ', Column J or I');
          $end_date = $this->calendarDate($completed, $context . ', Column K');
          if ($end_date < $start_date) {
            throw new \RuntimeException($context . ': Date Completed cannot precede the response time start date.');
          }
          // Share the Days Tolled calendar without subtracting tolled days.
          $days = $working_days->count($start, $completed);
          $histograms[$id][$track][$days] = ($histograms[$id][$track][$days] ?? 0) + 1;
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for processed response times.');
        }
      }
      finally {
        fclose($stream);
      }
    }
    return $histograms;
  }

  /**
   * Parses calendar dates defensively if a file changed after validation.
   */
  private function calendarDate(string $value, string $context): \DateTimeImmutable {
    if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $value, $parts)
      || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[3])) {
      throw new \RuntimeException($context . ': Expected a valid date in MM/DD/YYYY format.');
    }
    return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $parts[3], $parts[1], $parts[2]), new \DateTimeZone('UTC'));
  }

  /**
   * Finds exact medians and weighted averages from day frequencies.
   */
  private function summarize(array $histogram): array {
    if ($histogram === []) {
      return [];
    }
    ksort($histogram, SORT_NUMERIC);
    $count = array_sum($histogram);
    $lower_position = intdiv($count + 1, 2);
    $upper_position = intdiv($count, 2) + 1;
    $seen = 0;
    $sum = 0;
    $lower = NULL;
    $upper = NULL;
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
    return [
      'median' => ($lower + $upper) / 2,
      'average' => $sum / $count,
      'lowest' => array_key_first($histogram),
      'highest' => array_key_last($histogram),
    ];
  }

}
