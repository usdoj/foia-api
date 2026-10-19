<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Counts open requests and appeals exceeding their allowed working days.
 */
final class BacklogAggregator {

  /**
   * Counts only open requests and appeals, measured through fiscal year-end.
   *
   * @param array $sources
   *   Component/file pairs with component_id and uri keys, plus an optional
   *   component_label for error messages.
   * @param int $fiscal_year
   *   The validated report year.
   *
   * @return array
   *   Component counts keyed by entity ID and overall counts, keyed by outcome.
   */
  public function aggregate(array $sources, int $fiscal_year): array {
    $empty = ['requests' => 0, 'appeals' => 0];
    $year_end = sprintf('09/30/%04d', $fiscal_year);
    $working_days = new WorkingDays();
    $components = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= $empty;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for backlog counts.');
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
          $context = sprintf('Component %s, CSV %s, record %d', $source['component_label'] ?? $id, basename($source['uri']), $record);
          // Column D supplies the threshold for both requests and appeals.
          // Consultations leave Days Allowed blank and are excluded.
          $days_allowed = trim($columns[3]);
          if ($days_allowed === '') {
            continue;
          }
          if (!in_array($days_allowed, ['20', '30'], TRUE)) {
            throw new \RuntimeException($context . ': Column D: Expected Days Allowed to be 20 or 30.');
          }
          // Request backlog age starts only at I, without track filters.
          $request_start = trim($columns[8]);
          $intervals = [
            'requests' => [$request_start, trim($columns[10]), 'I', 'K'],
            'appeals' => [trim($columns[23]), trim($columns[24]), 'X', 'Y'],
          ];
          foreach ($intervals as $kind => [$start, $end, $start_column, $end_column]) {
            // Only items still open at fiscal year-end contribute to backlog.
            if ($end !== '') {
              continue;
            }
            // Rows without a received/perfected date do not identify an item.
            if ($start === '') {
              continue;
            }
            $start_date = $this->calendarDate($start, $context . ', Column ' . $start_column);
            $end = $year_end;
            $end_date = $this->calendarDate($end, $context . ', Column ' . $end_column);
            if ($end_date < $start_date) {
              throw new \RuntimeException($context . ': End date cannot precede start date for ' . $kind . '.');
            }
            // Use actual receipt dates, including time before the fiscal year.
            if ($working_days->count($start, $end) > (int) $days_allowed) {
              $components[$id][$kind]++;
            }
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for backlog counts.');
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
