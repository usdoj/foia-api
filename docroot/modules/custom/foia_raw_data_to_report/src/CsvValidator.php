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
   * @param int $fiscal_year
   *   The report node's Year; the fiscal year ends on September 30.
   *
   * @return string[]
   *   Validation errors. Stops at the first error to keep output bounded.
   */
  public function validate(string $uri, int $fiscal_year): array {
    // A missing or invalid report year must not bypass fiscal-year checks.
    if ($fiscal_year < 1 || $fiscal_year > 9999) {
      return ['The report Year must be between 1 and 9999 to validate CSV dates.'];
    }
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
        // K may be empty; Column I is validated separately below.
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

        // Column I: Date Initially Received is required for all rows.
        $initially_received = trim($columns[8]);
        if ($initially_received === '') {
          return [sprintf('CSV record %d: Data initially Received cannot be blank', $record)];
        }

        // Column I: Require a real calendar date in month/day/four-digit year
        // order. Allow single-digit months/days as used in the reference CSV.
        if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $initially_received, $date_parts)
          || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
          return [sprintf('CSV record %d: Date Initially Received must be a valid date in MM/DD/YYYY format', $record)];
        }

        // Column I: September 30 of the report year is the latest allowed date.
        // Compare calendar dates without time zones; earlier years are allowed.
        $received_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
        if ($received_date > $fiscal_year * 10000 + 930) {
          return [sprintf('CSV record %d: Date Initially Received is later than the fiscal year', $record)];
        }

        // Column J: Date Perfected is optional; validate only nonblank values.
        // The Column C check already requires J to be blank for consultations.
        $perfected = trim($columns[9]);
        if ($perfected !== '') {
          // Column J: Require a real date using the same format as Column I.
          if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $perfected, $date_parts)
            || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
            return [sprintf('CSV record %d: Date Perfected must be a valid date in MM/DD/YYYY format', $record)];
          }

          // Column J: The date cannot exceed September 30 of the report year.
          $perfected_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
          if ($perfected_date > $fiscal_year * 10000 + 930) {
            return [sprintf('CSV record %d: Date Perfected is later than the fiscal year', $record)];
          }

          // Column J: A perfected date requires an uppercase S, C or E in M.
          if (!in_array(trim($columns[12]), ['S', 'C', 'E'], TRUE)) {
            return [sprintf('CSV record %d: If data is entered in Column J, Column M must contain capital S, C, or E', $record)];
          }

          // Column J: Perfection cannot precede the initially received date.
          if ($perfected_date < $received_date) {
            return [sprintf('CSV record %d: Date Perfected cannot be prior to Date Initially Received', $record)];
          }
        }

        // Column K: Date Completed is required when N contains a disposition.
        $completed = trim($columns[10]);
        if ($completed === '' && trim($columns[13]) !== '') {
          return [sprintf('CSV record %d: Must complete Column K if Disposition is listed in Column N', $record)];
        }

        // Column K: Otherwise optional, including for consultation rows.
        if ($completed !== '') {
          // Column K: Require a real date using the same format as I and J.
          if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $completed, $date_parts)
            || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
            return [sprintf('CSV record %d: Date Completed must be a valid date in MM/DD/YYYY format', $record)];
          }

          // Column K: Completion must fall within the report's fiscal year,
          // from October 1 of the previous year through September 30 inclusive.
          $completed_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
          if ($completed_date < ($fiscal_year - 1) * 10000 + 1001 || $completed_date > $fiscal_year * 10000 + 930) {
            return [sprintf('CSV record %d: Date Completed must fall within the fiscal year (10/01/%04d through 09/30/%04d)', $record, $fiscal_year - 1, $fiscal_year)];
          }

          // Column K: Completion cannot precede J when a perfected date exists.
          if ($perfected !== '' && $completed_date < $perfected_date) {
            return [sprintf('CSV record %d: Date Completed cannot be prior to Date Perfected', $record)];
          }
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
