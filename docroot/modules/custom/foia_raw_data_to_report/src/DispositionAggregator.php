<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams validated component CSVs into disposition counts from Column N.
 */
final class DispositionAggregator {

  /**
   * From Appendix B of "Agency Spreadsheet Guidance".
   */
  public const DISPOSITIONS = [
    1 => 'Full Grant',
    2 => 'Partial Grant / Partial Denial',
    3 => 'Full Denial Based on Exemptions',
    4 => 'No Records',
    5 => 'All Records Referred to Another Component',
    6 => 'Request Withdrawn',
    7 => 'Fee-Related Reasons',
    8 => 'Records Not Reasonably Described',
    9 => 'Improper FOIA Request for Other Reason',
    10 => 'Not Agency Record',
    11 => 'Duplicate Request',
    12 => 'Other',
  ];

  /**
   * XML reason codes matching the example report and foia_export_xml.
   */
  public const NON_EXEMPTION_REASONS = [
    4 => 'NoRecords',
    5 => 'Referred',
    6 => 'Withdrawn',
    7 => 'FeeRelated',
    8 => 'NotDescribed',
    9 => 'ImproperRequest',
    10 => 'NotAgency',
    11 => 'Duplicate',
    12 => 'Other',
  ];

  /**
   * Counts each nonblank disposition once per row, then sums component counts.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   *
   * @return array
   *   Component counts keyed by entity ID and overall counts, keyed by code.
   */
  public function aggregate(array $sources): array {
    $empty = array_fill_keys(array_keys(self::DISPOSITIONS), 0);
    $components = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= $empty;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for disposition counts.');
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
          $code = trim($columns[13]);
          if ($code === '') {
            continue;
          }
          // Reject unmapped values rather than silently omitting requests.
          if (!preg_match('/^(?:[1-9]|1[0-2])$/', $code)) {
            throw new \RuntimeException(sprintf('Component %s, CSV record %d: Column N must contain a disposition code from 1 through 12.', $id, $record));
          }
          $components[$id][(int) $code]++;
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for disposition counts.');
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
