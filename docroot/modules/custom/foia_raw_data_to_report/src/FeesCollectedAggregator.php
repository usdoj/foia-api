<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams Column W into exact fee totals per component and agency.
 */
final class FeesCollectedAggregator {

  /**
   * Sums decimal fee amounts as integer cents, without retaining CSV rows.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   *
   * @return array
   *   Component totals keyed by entity ID and overall total, in integer cents.
   */
  public function aggregate(array $sources): array {
    $components = [];
    foreach ($sources as $source) {
      $id = $source['component_id'];
      $components[$id] ??= 0;
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for fees collected.');
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
          $value = trim($columns[22]);
          if ($value === '') {
            continue;
          }
          $context = sprintf('Component %s, CSV %s, record %d, Column W', $id, basename($source['uri']), $record);
          $cents = $this->parseCents($value, $context);
          $components[$id] = $this->addCents($components[$id], $cents, $context);
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for fees collected.');
        }
      }
      finally {
        fclose($stream);
      }
    }
    $overall = 0;
    foreach ($components as $amount) {
      $overall = $this->addCents($overall, $amount, 'Agency fees collected');
    }
    return ['components' => $components, 'overall' => $overall];
  }

  /**
   * Parses plain decimal amounts exactly, allowing signed adjustments.
   */
  private function parseCents(string $value, string $context): int {
    if (!preg_match('/^([+-]?)([0-9]+)(?:\.([0-9]{1,2}))?$/', $value, $parts)) {
      throw new \RuntimeException($context . ': Expected a decimal fee amount with at most two decimal places; received "' . $value . '".');
    }
    $digits = ltrim($parts[2] . str_pad($parts[3] ?? '', 2, '0'), '0');
    $limit = (string) PHP_INT_MAX;
    if (strlen($digits) > strlen($limit) || (strlen($digits) === strlen($limit) && strcmp($digits, $limit) > 0)) {
      throw new \RuntimeException($context . ': Fee amount exceeds the supported range.');
    }
    $cents = (int) $digits;
    return $parts[1] === '-' ? -$cents : $cents;
  }

  /**
   * Prevents an overflowing integer sum from silently becoming a float.
   */
  private function addCents(int $total, int $amount, string $context): int {
    $sum = $total + $amount;
    if (!is_int($sum) || $sum === PHP_INT_MIN) {
      throw new \RuntimeException($context . ': Fee total exceeds the supported range.');
    }
    return $sum;
  }

}
