<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Column V into fee-waiver outcome counts per component.
 */
final class FeeWaiverAggregator {

  /**
   * Uppercase Column V outcomes mapped to their counters.
   */
  public const OUTCOMES = ['G' => 'granted', 'D' => 'denied'];

  /**
   * Counts each G or D once per row, then sums component counts.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   * @param int $fiscal_year
   *   Report year used for outcomes without completion dates.
   *
   * @return array
   *   Component and overall outcome counts plus adjudication summaries.
   */
  public function aggregate(array $sources, int $fiscal_year): array {
    $empty = array_fill_keys(array_values(self::OUTCOMES), 0);
    $components = [];
    $histograms = [];
    $end_of_year = sprintf('09/30/%04d', $fiscal_year);
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= $empty;
      $histograms[$id] ??= [];
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for fee-waiver counts.');
      }
      try {
        $header = TRUE;
        while (($columns = fgetcsv($stream, 0, ',', '"', '')) !== FALSE) {
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
          // Count only G and D in V, independently of dates and other outcomes.
          $outcome = trim($columns[21]);
          if (isset(self::OUTCOMES[$outcome])) {
            $components[$id][self::OUTCOMES[$outcome]]++;
            $began = trim($columns[19]);
            $completed = trim($columns[20]);
            $days = AdjudicationStatistics::days($began, $completed === '' ? $end_of_year : $completed);
            $histograms[$id][$days] = ($histograms[$id][$days] ?? 0) + 1;
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for fee-waiver counts.');
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

}
