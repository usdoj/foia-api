<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Column AA into appeal non-exemption denial counts.
 */
final class AppealNonExemptionDenialAggregator {

  /**
   * Column AA reason labels mapped to annual report XML codes.
   */
  public const REASONS = [
    'No Records' => 'NoRecords',
    'Records Referred at the Initial Request Level' => 'Referred',
    'Request Withdrawn' => 'Withdrawn',
    'Fee-Related Reason' => 'FeeRelated',
    'Records not Reasonably Described' => 'NotDescribed',
    'Improper Request for Other Reasons' => 'ImproperRequest',
    'Not an Agency Record' => 'NotAgency',
    'Duplicate Request or Appeal' => 'Duplicate',
    'Request in Litigation' => 'InLitigation',
    'Appeal Based Solely on Denial of Request for Expedited Processing' => 'ExpeditedDenial',
    'Other' => 'Other',
  ];

  /**
   * Counts each distinct reason once per row, then sums component counts.
   *
   * @param array $sources
   *   Component/file pairs with component_id and uri keys, plus an optional
   *   component_label for error messages.
   *
   * @return array
   *   Component and overall counts keyed by XML reason code.
   */
  public function aggregate(array $sources): array {
    $empty = array_fill_keys(array_values(self::REASONS), 0);
    $components = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= $empty;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for appeal non-exemption denial counts.');
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
          $value = trim($columns[26]);
          if ($value === '') {
            continue;
          }
          // Split comma-separated reasons and count repeats once per appeal.
          $reasons = array_unique(array_map('trim', explode(',', $value)));
          foreach ($reasons as $reason) {
            // Unknown labels must not silently disappear from the report.
            if (!isset(self::REASONS[$reason])) {
              throw new \RuntimeException(sprintf('Component %s, CSV record %d: Unknown appeal denial reason "%s" in Column AA.', $source['component_label'] ?? $id, $record, $reason));
            }
            $components[$id][self::REASONS[$reason]]++;
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for appeal non-exemption denial counts.');
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
