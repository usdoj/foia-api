<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Columns Q, R, and S into expedited processing counts.
 */
final class ExpeditedProcessingAggregator {

  /**
   * Column S outcomes mapped to their counters.
   */
  public const OUTCOMES = ['G' => 'granted', 'D' => 'denied'];

  /**
   * Counts granted, denied, and adjudications within ten calendar days.
   *
   * @param array $sources
   *   Component/file pairs with component_id and uri keys, plus an optional
   *   component_label for error messages.
   * @param int $fiscal_year
   *   Report year used for outcomes without completion dates.
   *
   * @return array
   *   Component and overall outcome counts plus adjudication summaries.
   */
  public function aggregate(array $sources, int $fiscal_year): array {
    $empty = ['granted' => 0, 'denied' => 0, 'within_ten' => 0];
    $components = [];
    $histograms = [];
    $end_of_year = sprintf('09/30/%04d', $fiscal_year);
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= $empty;
      $histograms[$id] ??= [];
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for expedited processing counts.');
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
          // Appeal rows do not contribute to expedited processing statistics.
          if (trim($columns[23]) !== '') {
            continue;
          }
          // Only uppercase G and D outcomes in Column S contribute.
          $outcome = trim($columns[18]);
          if (!isset(self::OUTCOMES[$outcome])) {
            continue;
          }
          $components[$id][self::OUTCOMES[$outcome]]++;
          $received = trim($columns[16]);
          $determined = trim($columns[17]);
          $context = sprintf('Component %s, CSV %s, record %d', $source['component_label'] ?? $id, basename($source['uri']), $record);
          $start = $this->calendarDate($received, $context . ', Column Q');
          $end = $this->calendarDate($determined === '' ? $end_of_year : $determined, $context . ', Column R or fiscal year-end');
          if ($end < $start) {
            throw new \RuntimeException($context . ': Column R cannot precede Column Q.');
          }
          // Use elapsed calendar days, counting same-day outcomes as one day.
          $days = max(1, (int) $start->diff($end)->days);
          $histograms[$id][$days] = ($histograms[$id][$days] ?? 0) + 1;
          // Only actual determinations within ten calendar days qualify.
          if ($determined !== '' && $days <= 10) {
            $components[$id]['within_ten']++;
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for expedited processing counts.');
        }
      }
      finally {
        fclose($stream);
      }
    }
    $overall = $empty;
    foreach ($components as $counts) {
      foreach ($counts as $code => $quantity) {
        $overall[$code] += $quantity;
      }
    }
    $overall_histogram = [];
    foreach ($histograms as $id => $histogram) {
      $components[$id] += AdjudicationStatistics::summarize($histogram);
      foreach ($histogram as $days => $frequency) {
        $overall_histogram[$days] = ($overall_histogram[$days] ?? 0) + $frequency;
      }
    }
    $overall += AdjudicationStatistics::summarize($overall_histogram);
    return ['components' => $components, 'overall' => $overall];
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

}
