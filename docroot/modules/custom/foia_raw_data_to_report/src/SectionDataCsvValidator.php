<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Validates the two-record Section IX-XI CSV template.
 */
final class SectionDataCsvValidator {

  /**
   * Exact header values from OIP FY23 Section IX-XI Datav final.csv.
   */
  public const HEADERS = [
    'Full-Time Employees',
    'Equivalent Full-Time Employees',
    'Processing Costs',
    'Litigation Costs',
    'Fees Collected',
    'Subsection (c) Exclusions',
    'Subsection (a)(2) Records Posted by FOIA Office',
    'Subsection (a)(2) Records Posted by Program',
  ];

  /**
   * Returns all structure and numeric errors with CSV record/column context.
   */
  public function validate(string $uri): array {
    $stream = @fopen($uri, 'rb');
    if ($stream === FALSE) {
      return ['The Section IX-XI CSV file could not be opened. Please upload it again.'];
    }
    $errors = [];
    try {
      // Ignore a UTF-8 BOM before parsing, including before a quoted header.
      if (fread($stream, 3) !== "\xEF\xBB\xBF") {
        rewind($stream);
      }
      $record = 0;
      $has_header = FALSE;
      $data_rows = 0;
      while (($columns = fgetcsv($stream, 0, ',', '"', '')) !== FALSE) {
        $record++;
        if ($columns === [NULL]) {
          continue;
        }
        $is_header = !$has_header;
        $has_header = TRUE;
        if (!$is_header) {
          $data_rows++;
        }
        // Every nonblank record must have all eight template columns.
        if (count($columns) !== count(self::HEADERS)) {
          $errors[] = sprintf('CSV record %d has %d columns; expected 8 columns for Section IX-XI data.', $record, count($columns));
          continue;
        }
        foreach (self::HEADERS as $index => $header) {
          $column = chr(65 + $index);
          // Header spelling, capitalization, whitespace, and order must match.
          if ($is_header) {
            if ($columns[$index] !== $header) {
              $errors[] = sprintf('CSV record %d: Column %s: Header must be exactly "%s".', $record, $column, $header);
            }
            continue;
          }
          // B accepts decimal notation; all other cells require whole numbers.
          // Match text rather than casting, so large integer values stay exact.
          $value = trim($columns[$index]);
          $pattern = $index === 1 ? '/^[+-]?[0-9]+(?:\.[0-9]+)?$/' : '/^[+-]?[0-9]+$/';
          if (!preg_match($pattern, $value)) {
            $errors[] = sprintf('CSV record %d: Column %s: %s must be %s.', $record, $column, $header, $index === 1 ? 'a decimal number' : 'an integer');
          }
        }
      }
      if (!feof($stream)) {
        $errors[] = 'The Section IX-XI CSV file could not be read completely. Please upload it again.';
      }
      if (!$has_header) {
        $errors[] = 'The Section IX-XI CSV file is empty. Upload the header and one data row.';
      }
      elseif ($data_rows !== 1) {
        $errors[] = sprintf('The Section IX-XI CSV must contain exactly one data row after the header; found %d.', $data_rows);
      }
      return $errors;
    }
    finally {
      fclose($stream);
    }
  }

}
