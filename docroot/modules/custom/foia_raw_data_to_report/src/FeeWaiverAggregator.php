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
   *
   * @return array
   *   Component counts keyed by entity ID and overall counts, keyed by outcome.
   */
  public function aggregate(array $sources): array {
    $empty = array_fill_keys(array_values(self::OUTCOMES), 0);
    $components = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= $empty;
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
    return ['components' => $components, 'overall' => $overall];
  }

}
