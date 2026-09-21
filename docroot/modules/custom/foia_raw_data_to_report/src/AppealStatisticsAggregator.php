<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams validated CSVs into fiscal-year appeal statistics.
 */
final class AppealStatisticsAggregator {

  /**
   * Counters initialized even for a component with no appeal rows.
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
        throw new \RuntimeException('Unable to reopen a validated CSV for appeal statistics.');
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
          // Rows without an appeal do not contribute to any of the counters.
          $received_text = trim($columns[23]);
          $completed = trim($columns[24]);
          $context = sprintf('Component %s, CSV record %d', $source['component_id'], $record);
          if ($received_text === '') {
            if ($completed !== '') {
              throw new \RuntimeException($context . ': Appeal Date Closed requires an Appeal Date Received.');
            }
            continue;
          }
          $received = $this->calendarDate($received_text, $context . ', Column X');
          if ($received < $start) {
            $counts['pending_start']++;
          }
          elseif ($received <= $end) {
            $counts['received']++;
          }
          else {
            throw new \RuntimeException($context . ': Appeal Date Received is after the report fiscal year.');
          }

          // Count pending appeals directly from blank Column Y.
          if ($completed === '') {
            $counts['pending_end']++;
          }
          else {
            $completed_date = $this->calendarDate($completed, $context . ', Column Y');
            if ($completed_date < $received) {
              throw new \RuntimeException($context . ': Appeal Date Closed cannot precede Appeal Date Received.');
            }
            if ($completed_date >= $start && $completed_date <= $end) {
              $counts['processed']++;
            }
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for appeal statistics.');
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
   * Parses appeal dates, which do not yet have separate CSV validation rules.
   */
  private function calendarDate(string $value, string $context): int {
    if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $value, $parts)
      || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[3])) {
      throw new \RuntimeException($context . ': Expected a valid date in MM/DD/YYYY format.');
    }
    return (int) $parts[3] * 10000 + (int) $parts[1] * 100 + (int) $parts[2];
  }

  /**
   * Checks pending start + received - processed = pending end.
   */
  private function checkBalance(array $counts, string $label): void {
    if ($counts['pending_start'] + $counts['received'] - $counts['processed'] !== $counts['pending_end']) {
      throw new \RuntimeException('Appeal statistics do not balance for ' . $label . '.');
    }
  }

}
