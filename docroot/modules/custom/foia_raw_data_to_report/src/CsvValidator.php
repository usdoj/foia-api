<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Validates request CSV files without loading the entire file into memory.
 */
final class CsvValidator {

  /**
   * Column count in OIP Request Raw Data (FOIA Star submission) (final).csv.
   */
  public const EXPECTED_COLUMNS = 29;

  /**
   * Returns human-readable errors, or an empty array when validation passes.
   *
   * Record numbers include the header and blank records. Quoted multiline
   * values count as one record. Additional validation checks belong here.
   *
   * @param string $uri
   *   The CSV file URI, including Drupal stream wrapper URIs.
   *
   * @return string[]
   *   Validation errors. Stops at the first error to keep output bounded.
   */
  public function validate(string $uri): array {
    $stream = @fopen($uri, 'rb');
    if ($stream === FALSE) {
      return ['The CSV file could not be opened. Please upload it again.'];
    }

    try {
      $record = 0;
      $has_content = FALSE;
      while (($columns = fgetcsv($stream, 0, ',', '"', '')) !== FALSE) {
        $record++;
        // PHP represents an empty CSV record as a single NULL value.
        if ($columns === [NULL]) {
          continue;
        }
        $has_content = TRUE;
        if (count($columns) !== self::EXPECTED_COLUMNS) {
          return [sprintf('CSV record %d has %d columns; expected %d columns to match the OIP request raw data template. Please correct the file and try again.', $record, count($columns), self::EXPECTED_COLUMNS)];
        }
      }
      if (!feof($stream)) {
        return ['The CSV file could not be read completely. Please upload it again.'];
      }
      if (!$has_content) {
        return ['The CSV file is empty. Please upload a CSV with 29 columns matching the OIP request raw data template.'];
      }
      return [];
    }
    finally {
      fclose($stream);
    }
  }

}
