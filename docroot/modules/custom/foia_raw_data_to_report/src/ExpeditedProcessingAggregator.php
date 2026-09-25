<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Columns Q, R, and S into expedited processing counts.
 */
final class ExpeditedProcessingAggregator {

  /**
   * Column S outcomes mapped to their counters.
   */
  public const OUTCOMES = ['G' => 'granted', 'D' => 'denied'];

  /**
   * Counts granted, denied, and adjudications within ten working days.
   *
   * @param array $sources
   *   Component/file pairs with component_id and uri keys, plus an optional
   *   component_label for error messages.
   *
   * @return array
   *   Component counts keyed by entity ID and overall counts, keyed by outcome.
   */
  public function aggregate(array $sources): array {
    $empty = ['granted' => 0, 'denied' => 0, 'within_ten' => 0];
    $working_days = new WorkingDays();
    $components = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= $empty;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for expedited processing counts.');
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
          // Only uppercase G and D outcomes in Column S contribute.
          $outcome = trim($columns[18]);
          if (!isset(self::OUTCOMES[$outcome])) {
            continue;
          }
          $components[$id][self::OUTCOMES[$outcome]]++;
          $received = trim($columns[16]);
          $determined = trim($columns[17]);
          $context = sprintf('Component %s, CSV %s, record %d', $source['component_label'] ?? $id, basename($source['uri']), $record);
          $start = $this->calendarDate($received, $context . ', Column Q');
          // R may be blank: count the outcome, but no adjudication interval.
          if ($determined === '') {
            continue;
          }
          $end = $this->calendarDate($determined, $context . ', Column R');
          if ($end < $start) {
            throw new \RuntimeException($context . ': Column R cannot precede Column Q.');
          }
          // Exclude the start day and include the end day; ten is inclusive.
          if ($working_days->count($received, $determined) <= 10) {
            $components[$id]['within_ten']++;
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for expedited processing counts.');
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

  /**
   * Parses calendar dates defensively if a file changed after validation.
   */
  private function calendarDate(string $value, string $context): \DateTimeImmutable {
    if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $value, $parts)
      || !checkdate((int) $parts[1], (int) $parts[2], (int) $parts[3])) {
      throw new \RuntimeException($context . ': Expected a valid date in MM/DD/YYYY format.');
    }
    return new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $parts[3], $parts[1], $parts[2]), new \DateTimeZone('UTC'));
  }

}
