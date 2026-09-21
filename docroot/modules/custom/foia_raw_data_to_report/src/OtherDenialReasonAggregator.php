<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams CSVs into request or appeal free-text denial reason counts.
 */
final class OtherDenialReasonAggregator {

  /**
   * Counts each nonblank reason once per row, then sums across components.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   * @param int $column
   *   Zero-based CSV column: 14 for requests, 27 for appeals.
   *
   * @return array
   *   Component and overall reason maps, with description and quantity entries.
   */
  public function aggregate(array $sources, int $column = 14): array {
    if (!in_array($column, [14, 27], TRUE)) {
      throw new \InvalidArgumentException('Other denial reasons require Column O or AB.');
    }
    $components = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= [];
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for other denial reasons.');
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
          // Column AB holds comma-separated reasons; Column O is one reason.
          $values = $column === 27 ? explode(',', $columns[$column]) : [$columns[$column]];
          // Count each reason once per row, trimming surrounding whitespace.
          // Preserve case, punctuation, and internal whitespace distinctions.
          foreach (array_unique(array_map('trim', $values)) as $reason) {
            if ($reason === '') {
              continue;
            }
            // A prefix preserves numeric-looking reasons as text keys.
            $key = 'reason:' . $reason;
            $components[$id][$key] ??= ['description' => $reason, 'quantity' => 0];
            $components[$id][$key]['quantity']++;
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for other denial reasons.');
        }
      }
      finally {
        fclose($stream);
      }
    }

    $overall = [];
    foreach ($components as $id => $reasons) {
      // Stable text order makes repeated report generation easy to compare.
      ksort($components[$id], SORT_STRING);
      foreach ($reasons as $key => $reason) {
        $overall[$key] ??= ['description' => $reason['description'], 'quantity' => 0];
        $overall[$key]['quantity'] += $reason['quantity'];
      }
    }
    ksort($overall, SORT_STRING);
    return ['components' => $components, 'overall' => $overall];
  }

}
