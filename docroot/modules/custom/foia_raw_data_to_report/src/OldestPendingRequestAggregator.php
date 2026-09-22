<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Columns I and K into the ten oldest pending requests.
 */
final class OldestPendingRequestAggregator {

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
    $working_days = new WorkingDays();
    $components = [];
    $overall = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= [];
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for oldest pending requests.');
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
          // Pending requests have a received date and no completed date.
          $received_text = trim($columns[8]);
          if ($received_text === '' || trim($columns[10]) !== '') {
            continue;
          }
          $context = sprintf('Component %s, CSV %s, record %d, Column I', $id, basename($source['uri']), $record);
          $received = $this->calendarDate($received_text, $context);
          if ($received > $end) {
            throw new \RuntimeException($context . ': Date Initially Received is after the report fiscal year.');
          }
          // Use elapsed working days, including time in prior fiscal years.
          $item = [
            'receipt_date' => $received->format('Y-m-d'),
            'pending_days' => $working_days->count($received_text, $end->format('m/d/Y')),
          ];
          $this->retainOldest($components[$id], $item);
          $this->retainOldest($overall, $item);
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for oldest pending requests.');
        }
      }
      finally {
        fclose($stream);
      }
    }
    return ['components' => $components, 'overall' => $overall];
  }

  /**
   * Keeps the ten highest ages, breaking ties by the earliest receipt date.
   */
  private function retainOldest(array &$items, array $item): void {
    $items[] = $item;
    // Preserve duplicate rows; equal working-day ages can span weekends.
    usort($items, static fn(array $a, array $b): int => ($b['pending_days'] <=> $a['pending_days']) ?: strcmp($a['receipt_date'], $b['receipt_date']));
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
