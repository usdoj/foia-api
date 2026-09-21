<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Columns X and Y into the ten oldest open appeals.
 */
final class OldestPendingAppealAggregator {

  /**
   * Collects at most ten receipt dates per component and for the agency.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   * @param int $fiscal_year
   *   The validated report year.
   *
   * @return array
   *   Component and overall lists with receipt_date and pending_days entries.
   */
  public function aggregate(array $sources, int $fiscal_year): array {
    $end = new \DateTimeImmutable($fiscal_year . '-09-30', new \DateTimeZone('UTC'));
    $components = [];
    $overall = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= [];
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for oldest pending appeals.');
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
          // Only appeals with a received date and no closed date are pending.
          $received_text = trim($columns[23]);
          if ($received_text === '' || trim($columns[24]) !== '') {
            continue;
          }
          $context = sprintf('Component %s, CSV record %d, Column X', $id, $record);
          $received = $this->calendarDate($received_text, $context);
          if ($received > $end) {
            throw new \RuntimeException($context . ': Appeal Date Received is after the report fiscal year.');
          }
          // Use the full elapsed calendar time, including prior fiscal years.
          $item = [
            'receipt_date' => $received->format('Y-m-d'),
            'pending_days' => (int) $received->diff($end)->days,
          ];
          $this->retainOldest($components[$id], $item);
          $this->retainOldest($overall, $item);
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for oldest pending appeals.');
        }
      }
      finally {
        fclose($stream);
      }
    }
    return ['components' => $components, 'overall' => $overall];
  }

  /**
   * Keeps the earliest ten dates, retaining repeated dates as separate rows.
   */
  private function retainOldest(array &$items, array $item): void {
    $items[] = $item;
    usort($items, static fn(array $a, array $b): int => strcmp($a['receipt_date'], $b['receipt_date']));
    if (count($items) > 10) {
      array_pop($items);
    }
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
