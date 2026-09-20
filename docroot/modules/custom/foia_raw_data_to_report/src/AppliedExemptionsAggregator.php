<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams validated component CSVs into applied exemption counts from Column P.
 */
final class AppliedExemptionsAggregator {

  /**
   * CSV exemption codes mapped to the annual report's XML labels.
   */
  public const EXEMPTIONS = [
    '1' => 'Ex. 1',
    '2' => 'Ex. 2',
    '3' => 'Ex. 3',
    '4' => 'Ex. 4',
    '5' => 'Ex. 5',
    '6' => 'Ex. 6',
    '7A' => 'Ex. 7(A)',
    '7B' => 'Ex. 7(B)',
    '7C' => 'Ex. 7(C)',
    '7D' => 'Ex. 7(D)',
    '7E' => 'Ex. 7(E)',
    '7F' => 'Ex. 7(F)',
    '8' => 'Ex. 8',
    '9' => 'Ex. 9',
  ];

  /**
   * Counts each distinct exemption once per row, then sums component counts.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   *
   * @return array
   *   Component counts keyed by entity ID and overall counts, keyed by code.
   */
  public function aggregate(array $sources): array {
    $empty = array_fill_keys(array_keys(self::EXEMPTIONS), 0);
    $components = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= $empty;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for applied exemption counts.');
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
          $value = trim($columns[15]);
          if ($value === '') {
            continue;
          }
          // Split the CSV cell, ignore surrounding whitespace and normalize
          // letter case. Repeated codes count once for this request.
          $codes = array_unique(array_map(static fn(string $code) => strtoupper(trim($code)), explode(',', $value)));
          foreach ($codes as $code) {
            // Unknown codes must not silently disappear from the report.
            if (!isset(self::EXEMPTIONS[$code])) {
              throw new \RuntimeException(sprintf('Component %s, CSV record %d: Unknown exemption code "%s" in Column P.', $id, $record, $code));
            }
            $components[$id][$code]++;
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for applied exemption counts.');
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
