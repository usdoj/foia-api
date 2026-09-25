<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Columns X and Y into working-day response time distributions.
 */
final class AppealResponseTimeAggregator {

  /**
   * Calculates component and agency statistics without retaining CSV rows.
   *
   * @param array $sources
   *   Component/file pairs with component_id and uri keys, plus an optional
   *   component_label for error messages.
   * @param int $fiscal_year
   *   The validated report year.
   *
   * @return array
   *   Component and overall median, average, lowest, and highest values.
   */
  public function aggregate(array $sources, int $fiscal_year): array {
    $timezone = new \DateTimeZone('UTC');
    $start = new \DateTimeImmutable(($fiscal_year - 1) . '-10-01', $timezone);
    $end = new \DateTimeImmutable($fiscal_year . '-09-30', $timezone);
    $working_days = new WorkingDays();
    $histograms = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $histograms[$id] ??= [];
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for appeal response times.');
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
          $context = sprintf('Component %s, CSV record %d', $source['component_label'] ?? $id, $record);
          // Only rows with an appeal contribute to response time statistics.
          $received_text = trim($columns[23]);
          $completed = trim($columns[24]);
          if ($received_text === '') {
            if ($completed !== '') {
              throw new \RuntimeException($context . ': Appeal Date Closed requires an Appeal Date Received.');
            }
            continue;
          }
          $received = $this->calendarDate($received_text, $context . ', Column X');
          $finish = $completed === '' ? $end : $this->calendarDate($completed, $context . ', Column Y');
          $begin = $received > $start ? $received : $start;
          if ($finish < $begin || $finish > $end) {
            throw new \RuntimeException($context . ': Response time dates must be in chronological order and within the report fiscal year.');
          }
          // Working days exclude the starting day; same-day is zero.
          // UTC dates avoid daylight-saving changes affecting the calculation.
          $days = $working_days->count($begin->format('m/d/Y'), $finish->format('m/d/Y'));
          $histograms[$id][$days] = ($histograms[$id][$days] ?? 0) + 1;
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for appeal response times.');
        }
      }
      finally {
        fclose($stream);
      }
    }
    $components = [];
    $overall = [];
    foreach ($histograms as $id => $histogram) {
      $components[$id] = $this->summarize($histogram);
      // Combine frequencies so agency statistics are weighted by appeal count.
      foreach ($histogram as $days => $count) {
        $overall[$days] = ($overall[$days] ?? 0) + $count;
      }
    }
    return ['components' => $components, 'overall' => $this->summarize($overall)];
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
      return ['median' => 0, 'average' => 0, 'lowest' => 0, 'highest' => 0];
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
      'average' => round($sum / $count, 2),
      'lowest' => array_key_first($histogram),
      'highest' => array_key_last($histogram),
    ];
  }

}
