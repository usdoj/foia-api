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
      $request_numbers = [];
      while (($columns = fgetcsv($stream, 0, ',', '"', '')) !== FALSE) {
        $record++;
        // PHP represents an empty CSV record as a single NULL value.
        if ($columns === [NULL]) {
          continue;
        }
        if (count($columns) !== self::EXPECTED_COLUMNS) {
          return [sprintf('CSV record %d has %d columns; expected %d columns to match the OIP request raw data template. Please correct the file and try again.', $record, count($columns), self::EXPECTED_COLUMNS)];
        }
        // The first nonblank record is the header; only check its column count.
        if (!$has_content) {
          $has_content = TRUE;
          continue;
        }

        // Column A: Component is required, including for consultation rows.
        if (trim($columns[0]) === '') {
          return [sprintf('CSV record %d: Component cannot be blank', $record)];
        }

        // Column B: Request Number is required and must be unique in this CSV.
        // Keep only the numbers seen so far, with a prefix to preserve string
        // keys (including leading zeroes). Ignore surrounding whitespace.
        $request_number = trim($columns[1]);
        if ($request_number === '') {
          return [sprintf('CSV record %d: Request Number cannot be blank', $record)];
        }
        $request_key = 'request:' . $request_number;
        if (isset($request_numbers[$request_key])) {
          return [sprintf('CSV record %d: Request Number is duplicate', $record)];
        }
        $request_numbers[$request_key] = TRUE;

        // Column C: Is This a Consultation must be an uppercase Y or N.
        $consultation = trim($columns[2]);
        if (!in_array($consultation, ['Y', 'N'], TRUE)) {
          return [sprintf("CSV record %d: Column C must have 'Y' or 'N' entered", $record)];
        }

        // Column C: Consultations may contain values only in A, B, C, I and K.
        // K may be empty; date validation for I and K will be added separately.
        if ($consultation === 'Y') {
          foreach ($columns as $index => $value) {
            if (!in_array($index, [0, 1, 2, 8, 10], TRUE) && trim($value) !== '') {
              // This 29-column template spans A through AC.
              $column = $index < 26 ? chr(65 + $index) : 'A' . chr(65 + $index - 26);
              return [sprintf("CSV record %d: Column %s must be blank when Column C is 'Y'; only columns A, B, C, I, and K may contain values", $record, $column)];
            }
          }
        }

        // Column D: Non-consultation rows require Days Allowed to be 20 or 30.
        if ($consultation !== 'Y' && !in_array(trim($columns[3]), ['20', '30'], TRUE)) {
          return [sprintf('CSV record %d: Must complete Days Allowed with 20 or 30', $record)];
        }

        // Column E: Exemption 3 Statutes is optional. The Column C check above
        // already requires it to be blank for consultation rows.
        $statutes = trim($columns[4]);
        $has_other_statute = FALSE;
        if ($statutes !== '') {
          // Column E: Multiple IDs must use commas, not another separator.
          // Detect digit groups separated by non-comma characters; whitespace
          // around comma-separated IDs is allowed, as with other column checks.
          if (preg_match('/[0-9][^0-9,]+[0-9]/', $statutes)) {
            return [sprintf('CSV record %d: Separate multiple entries in Column E with a comma', $record)];
          }

          // Column E: Every comma-separated entry must be an integer ID 1-77.
          // Empty entries (including trailing commas) are invalid IDs.
          $statute_ids = [];
          foreach (explode(',', $statutes) as $statute) {
            $statute = trim($statute);
            if (!ctype_digit($statute) || (int) $statute < 1 || (int) $statute > 77) {
              return [sprintf('CSV record %d: Entries in Column E must be a valid ID between 1 - 77', $record)];
            }
            $statute_ids[] = (int) $statute;
          }

          // Column E: Any list containing 77 requires all of F, G and H.
          // Lists with only IDs 1-76 require all three fields to be blank.
          $has_other_statute = in_array(77, $statute_ids, TRUE);
          foreach ([5, 6, 7] as $index) {
            if ($has_other_statute && trim($columns[$index]) === '') {
              return [sprintf('CSV record %d: If Ex. 3 Code 77 is entered, columns F, G, and H must contain information', $record)];
            }
            if (!$has_other_statute && trim($columns[$index]) !== '') {
              return [sprintf('CSV record %d: If Ex. 3 Codes 1 - 76 is entered in Column E, columns F, G, and H must remain blank', $record)];
            }
          }

          // Column E: Statute information requires exemption 3 in Column P.
          // Match a complete comma-separated exemption, not a substring of 13.
          $exemptions = array_map('trim', explode(',', $columns[15]));
          if (!in_array('3', $exemptions, TRUE)) {
            return [sprintf("CSV record %d: If Column E contains information, a '3' must be listed in Column P", $record)];
          }
        }

        // Column F: Other Exemption 3 Statutes requires code 77 in Column E.
        if (trim($columns[5]) !== '' && !$has_other_statute) {
          return [sprintf('CSV record %d: Ex. 3 Code 77 must appear in Column E if there is data in Column F', $record)];
        }

        // Column G: Information Withheld requires code 77 in E and data in F.
        if (trim($columns[6]) !== '' && (!$has_other_statute || trim($columns[5]) === '')) {
          return [sprintf('CSV record %d: Ex. 3 Code 77 must appear in Column E if there is data in Column G', $record)];
        }

        // Column H: Case Citation requires code 77 in E and data in F and G.
        if (trim($columns[7]) !== '' && (!$has_other_statute || trim($columns[5]) === '' || trim($columns[6]) === '')) {
          return [sprintf('CSV record %d: Ex. 3 Code 77 must appear in Column E if there is data in Column H', $record)];
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
