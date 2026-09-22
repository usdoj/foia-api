<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams pending perfected requests into age statistics by track.
 */
final class PendingPerfectedRequestsAggregator {

  /**
   * Track codes mapped to pending statistics elements, in XML order.
   */
  public const TRACKS = [
    'S' => 'SimplePendingRequestStatistics',
    'C' => 'ComplexPendingRequestStatistics',
    'E' => 'ExpeditedPendingRequestStatistics',
  ];

  /**
   * Calculates component and agency counts, medians, and averages.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   * @param int $fiscal_year
   *   The validated report year, ending September 30.
   *
   * @return array
   *   Component and overall statistics keyed by track.
   */
  public function aggregate(array $sources, int $fiscal_year): array {
    $end = new \DateTimeImmutable($fiscal_year . '-09-30', new \DateTimeZone('UTC'));
    $working_days = new WorkingDays();
    $empty = array_fill_keys(array_keys(self::TRACKS), []);
    $histograms = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $histograms[$id] ??= $empty;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for pending perfected requests.');
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
          // Include only perfected requests that have not been completed.
          $perfected = trim($columns[9]);
          if ($perfected === '' || trim($columns[10]) !== '') {
            continue;
          }
          $context = sprintf('Component %s, CSV %s, record %d', $id, basename($source['uri']), $record);
          $track = trim($columns[12]);
          if (!isset(self::TRACKS[$track])) {
            throw new \RuntimeException($context . ': Column M must contain S, C, or E for a perfected request.');
          }
          $start = $this->calendarDate($perfected, $context . ', Column J');
          if ($start > $end) {
            throw new \RuntimeException($context . ': Column J Date Perfected is later than the fiscal year.');
          }
          // Measure from the actual perfected date, including prior years.
          $days = $working_days->count($perfected, $end->format('m/d/Y'));
          $histograms[$id][$track][$days] = ($histograms[$id][$track][$days] ?? 0) + 1;
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for pending perfected requests.');
        }
      }
      finally {
        fclose($stream);
      }
    }
    $components = [];
    $overall = $empty;
    foreach ($histograms as $id => $tracks) {
      foreach ($tracks as $track => $histogram) {
        $components[$id][$track] = $this->summarize($histogram);
        // Combine frequencies, rather than averaging component statistics.
        foreach ($histogram as $days => $count) {
          $overall[$track][$days] = ($overall[$track][$days] ?? 0) + $count;
        }
      }
    }
    foreach ($overall as $track => $histogram) {
      $overall[$track] = $this->summarize($histogram);
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

  /**
   * Finds exact medians and weighted averages from day frequencies.
   */
  private function summarize(array $histogram): array {
    if ($histogram === []) {
      return ['quantity' => 0, 'median' => NULL, 'average' => NULL];
    }
    ksort($histogram, SORT_NUMERIC);
    $count = array_sum($histogram);
    $lower_position = intdiv($count + 1, 2);
    $upper_position = intdiv($count, 2) + 1;
    $seen = 0;
    $sum = 0;
    $lower = NULL;
    $upper = NULL;
    foreach ($histogram as $days => $frequency) {
      $seen += $frequency;
      $sum += $days * $frequency;
      if ($lower === NULL && $seen >= $lower_position) {
        $lower = $days;
      }
      if ($upper === NULL && $seen >= $upper_position) {
        $upper = $days;
      }
    }
    return [
      'quantity' => $count,
      'median' => ($lower + $upper) / 2,
      'average' => $sum / $count,
    ];
  }

}
