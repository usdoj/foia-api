<?php

namespace Drupal\foia_raw_data_to_report;

use Brick\Math\BigDecimal;

/**
 * Collects exact personnel, cost, fee, and exclusion totals from Section IX-XI.
 */
final class PersonnelAndCostAggregator {

  /**
   * Returns component values and agency sums, keyed by XML element name.
   */
  public function aggregate(array $sources): array {
    $fields = [
      'FullTimeEmployeeQuantity',
      'EquivalentFullTimeEmployeeQuantity',
      'TotalFullTimeStaffQuantity',
      'ProcessingCostAmount',
      'LitigationCostAmount',
      'TotalCostAmount',
      'FeesCollectedAmount',
      'TimesUsedQuantity',
    ];
    $overall = array_fill_keys($fields, '0');
    $components = [];
    foreach ($sources as $source) {
      // Recheck the small file in case it changed after queue validation.
      $errors = (new SectionDataCsvValidator())->validate($source['uri']);
      $context = sprintf('Component %s, CSV %s', $source['component_id'], basename($source['uri']));
      if ($errors) {
        throw new \RuntimeException($context . ': ' . implode(' ', $errors));
      }
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException($context . ': Unable to reopen Section IX-XI CSV.');
      }
      try {
        if (fread($stream, 3) !== "\xEF\xBB\xBF") {
          rewind($stream);
        }
        $rows = [];
        while (($row = fgetcsv($stream, 0, ',', '"', '')) !== FALSE) {
          if ($row !== [NULL]) {
            $rows[] = $row;
          }
          // Retain only this template's two records, even if the file changed.
          if (count($rows) > 2) {
            break;
          }
        }
        if (!feof($stream) || count($rows) !== 2 || $rows[0] !== SectionDataCsvValidator::HEADERS || count($rows[1]) !== 8) {
          throw new \RuntimeException($context . ': Section IX-XI CSV changed after validation.');
        }
        $values = [];
        foreach (array_slice($rows[1], 0, 6) as $index => $value) {
          $value = trim($value);
          $pattern = $index === 1 ? '/^[+-]?[0-9]+(?:\.[0-9]+)?$/' : '/^[+-]?[0-9]+$/';
          if (!preg_match($pattern, $value)) {
            throw new \RuntimeException($context . ': Column ' . chr(65 + $index) . ': Invalid number after validation.');
          }
          $values[] = BigDecimal::of($value);
        }
        [$employees, $equivalent, $processing, $litigation, $fees, $exclusions] = $values;
        $totals = [
          $employees,
          $equivalent,
          $employees->plus($equivalent),
          $processing,
          $litigation,
          $processing->plus($litigation),
          $fees,
          $exclusions,
        ];
        $id = $source['component_id'];
        $components[$id] ??= array_fill_keys($fields, '0');
        foreach ($fields as $index => $field) {
          $components[$id][$field] = (string) BigDecimal::of($components[$id][$field])->plus($totals[$index])->strippedOfTrailingZeros();
          $overall[$field] = (string) BigDecimal::of($overall[$field])->plus($totals[$index])->strippedOfTrailingZeros();
        }
      }
      finally {
        fclose($stream);
      }
    }
    return ['components' => $components, 'overall' => $overall];
  }

}
