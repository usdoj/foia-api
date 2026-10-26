<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Column Z into appeal disposition counts per component.
 */
final class AppealDispositionAggregator {

  /**
   * Column Z integer codes mapped to their disposition labels.
   */
  public const DISPOSITIONS = [
    1 => 'Affirmed on Appeal',
    2 => 'Partially Affirmed & Partially Reversed/Remanded',
    3 => 'Completely Reversed/Remanded on Appeal',
    4 => 'Appeal Closed for Other Reason',
  ];

  /**
   * Integer codes mapped to the existing XML counters.
   */
  private const COUNTERS = [
    1 => 'affirmed',
    2 => 'partial',
    3 => 'reversed',
    4 => 'other',
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
    $empty = array_fill_keys(array_values(self::COUNTERS), 0);
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
          if (!preg_match('/^[1-4]$/', $code)) {
            throw new \RuntimeException(sprintf('Component %s, CSV record %d: Column Z (Appeal Disposition) must be an integer from 1 to 4.', $source['component_label'] ?? $id, $record));
          }
          $components[$id][self::COUNTERS[(int) $code]]++;
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
