<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams validated CSVs into fiscal-year request statistics.
 */
final class RequestStatisticsAggregator {

  /**
   * Counters initialized even for a component with no request rows.
   */
  private const EMPTY_COUNTS = [
    'pending_start' => 0,
    'received' => 0,
    'processed' => 0,
    'pending_end' => 0,
  ];

  /**
   * Returns component counters and agency totals without retaining CSV rows.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   * @param int $fiscal_year
   *   The validated report year.
   *
   * @return array
   *   Component counts keyed by entity ID, plus overall totals.
   */
  public function aggregate(array $sources, int $fiscal_year): array {
    $start = ($fiscal_year - 1) * 10000 + 1001;
    $end = $fiscal_year * 10000 + 930;
    $components = [];
    foreach ($sources as $source) {
      $counts = self::EMPTY_COUNTS;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for request statistics.');
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
          // Every data row counts, including consultations and rows without E.
          $context = sprintf('Component %s, CSV %s, record %d', $source['component_id'], basename($source['uri']), $record);
          $received = $this->calendarDate(trim($columns[8]), $context . ', Column I (Date Initially Received)');
          if ($received < $start) {
            $counts['pending_start']++;
          }
          elseif ($received <= $end) {
            $counts['received']++;
          }
          else {
            throw new \RuntimeException('CSV received date is after the report fiscal year.');
          }

          // Count end-of-year pending requests directly from blank Column K.
          $completed = trim($columns[10]);
          if ($completed === '') {
            $counts['pending_end']++;
          }
          else {
            $completed_date = $this->calendarDate($completed, $context . ', Column K (Date Completed)');
            if ($completed_date >= $start && $completed_date <= $end) {
              $counts['processed']++;
            }
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for request statistics.');
        }
      }
      finally {
        fclose($stream);
      }
      // The counts must reconcile before any XML file can be replaced.
      $this->checkBalance($counts, (string) $source['component_id']);
      $id = $source['component_id'];
      $components[$id] ??= self::EMPTY_COUNTS;
      foreach ($counts as $key => $value) {
        $components[$id][$key] += $value;
      }
    }

    $overall = self::EMPTY_COUNTS;
    foreach ($components as $counts) {
      foreach ($counts as $key => $value) {
        $overall[$key] += $value;
      }
    }
    $this->checkBalance($overall, 'agency overall');
    return ['components' => $components, 'overall' => $overall];
  }

  /**
   * Converts a validated date to YYYYMMDD, guarding against changed CSV data.
   */
  private function calendarDate(string $value, string $context): int {
    if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $value, $parts)
      || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[3])) {
      throw new \RuntimeException($context . ': CSV contains an invalid date after validation: ' . ($value === '' ? '[blank]' : $value) . '. Expected MM/DD/YYYY.');
    }
    return (int) $parts[3] * 10000 + (int) $parts[1] * 100 + (int) $parts[2];
  }

  /**
   * Checks pending start + received - processed = pending end.
   */
  private function checkBalance(array $counts, string $label): void {
    if ($counts['pending_start'] + $counts['received'] - $counts['processed'] !== $counts['pending_end']) {
      throw new \RuntimeException('Request statistics do not balance for ' . $label . '.');
    }
  }

}
