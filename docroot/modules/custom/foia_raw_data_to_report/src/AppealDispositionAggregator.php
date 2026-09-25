<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Column Z into appeal disposition counts per component.
 */
final class AppealDispositionAggregator {

  /**
   * Exact Column Z values mapped to their counters.
   */
  public const DISPOSITIONS = [
    'Affirmed on Appeal' => 'affirmed',
    'Partially Affirmed & Partially Reversed/Remanded' => 'partial',
    'Completely Reversed/Remanded' => 'reversed',
    'Closed for Other Reasons' => 'other',
  ];

  /**
   * Counts each nonblank disposition once per row, then sums component counts.
   *
   * @param array $sources
   *   Component/file pairs with component_id and uri keys, plus an optional
   *   component_label for error messages.
   *
   * @return array
   *   Component counts keyed by entity ID and overall counts, keyed by outcome.
   */
  public function aggregate(array $sources): array {
    $empty = array_fill_keys(array_values(self::DISPOSITIONS), 0);
    $components = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= $empty;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for appeal disposition counts.');
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
          $code = trim($columns[25]);
          if ($code === '') {
            continue;
          }
          // Reject unmapped values rather than silently omitting requests.
          if (!isset(self::DISPOSITIONS[$code])) {
            throw new \RuntimeException(sprintf('Component %s, CSV record %d: Unknown Appeal Disposition in Column Z.', $source['component_label'] ?? $id, $record));
          }
          $components[$id][self::DISPOSITIONS[$code]]++;
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for appeal disposition counts.');
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
