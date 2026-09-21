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

        // Column X: Appeal rows must not contain request data in E through W.
        $appeal_received = trim($columns[23]);
        if ($appeal_received !== '') {
          foreach (array_slice($columns, 4, 19) as $value) {
            if (trim($value) !== '') {
              return [sprintf('CSV record %d: If there is data in Column X, Columns E through W must be empty.', $record)];
            }
          }
        }

        // Column A: Component is required, including for consultation rows.
        if (trim($columns[0]) === '') {
          return [sprintf('CSV record %d: Column A: Component cannot be blank', $record)];
        }

        // Column B: Request Number is required and must be unique in this CSV.
        // Keep only the numbers seen so far, with a prefix to preserve string
        // keys (including leading zeroes). Ignore surrounding whitespace.
        $request_number = trim($columns[1]);
        if ($request_number === '') {
          return [sprintf('CSV record %d: Column B: Request Number cannot be blank', $record)];
        }
        $request_key = 'request:' . $request_number;
        if (isset($request_numbers[$request_key])) {
          return [sprintf('CSV record %d: Column B: Request Number is duplicate', $record)];
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
          return [sprintf('CSV record %d: Column D: Must complete Days Allowed with 20 or 30', $record)];
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
            return [sprintf('CSV record %d: Column E: Separate multiple entries in Column E with a comma', $record)];
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
              return [sprintf('CSV record %d: Column E: If Ex. 3 Code 77 is entered, columns F, G, and H must contain information', $record)];
            }
            if (!$has_other_statute && trim($columns[$index]) !== '') {
              return [sprintf('CSV record %d: Column E: If Ex. 3 Codes 1 - 76 is entered in Column E, columns F, G, and H must remain blank', $record)];
            }
          }

          // Column E: Statute information requires exemption 3 in Column P.
          // Match a complete comma-separated exemption, not a substring of 13.
          $exemptions = array_map('trim', explode(',', $columns[15]));
          if (!in_array('3', $exemptions, TRUE)) {
            return [sprintf("CSV record %d: Column E: If Column E contains information, a '3' must be listed in Column P", $record)];
          }
        }

        // Column F: Other Exemption 3 Statutes requires code 77 in Column E.
        if (trim($columns[5]) !== '' && !$has_other_statute) {
          return [sprintf('CSV record %d: Column F: Ex. 3 Code 77 must appear in Column E if there is data in Column F', $record)];
        }

        // Column G: Information Withheld requires code 77 in E and data in F.
        if (trim($columns[6]) !== '' && (!$has_other_statute || trim($columns[5]) === '')) {
          return [sprintf('CSV record %d: Column G: Ex. 3 Code 77 must appear in Column E if there is data in Column G', $record)];
        }

        // Column H: Case Citation requires code 77 in E and data in F and G.
        if (trim($columns[7]) !== '' && (!$has_other_statute || trim($columns[5]) === '' || trim($columns[6]) === '')) {
          return [sprintf('CSV record %d: Column H: Ex. 3 Code 77 must appear in Column E if there is data in Column H', $record)];
        }

        // Column I: Optional, but the Column X check requires it to be blank
        // for appeal rows. Validate date contents only when present.
        $initially_received = trim($columns[8]);
        $received_date = NULL;
        if ($initially_received !== '') {
          // Column I: Require a real calendar date in month/day/four-digit year
          // order. Allow single-digit months/days as used in the reference CSV.
          if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $initially_received, $date_parts)
            || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
            return [sprintf('CSV record %d: Column I: Date Initially Received must be a valid date in MM/DD/YYYY format', $record)];
          }

          // Column I: September 30 of the report year is the latest date.
          // Compare dates without time zones; earlier years are allowed.
          $received_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
          if ($received_date > $fiscal_year * 10000 + 930) {
            return [sprintf('CSV record %d: Column I: Date Initially Received is later than the fiscal year', $record)];
          }
        }

        // Column J: Date Perfected is optional; validate only nonblank values.
        // The Column C check already requires J to be blank for consultations.
        $perfected = trim($columns[9]);
        if ($perfected !== '') {
          // Column J: Require a real date using the same format as Column I.
          if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $perfected, $date_parts)
            || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
            return [sprintf('CSV record %d: Column J: Date Perfected must be a valid date in MM/DD/YYYY format', $record)];
          }

          // Column J: The date cannot exceed September 30 of the report year.
          $perfected_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
          if ($perfected_date > $fiscal_year * 10000 + 930) {
            return [sprintf('CSV record %d: Column J: Date Perfected is later than the fiscal year', $record)];
          }

          // Column J: A perfected date requires an uppercase S, C or E in M.
          if (!in_array(trim($columns[12]), ['S', 'C', 'E'], TRUE)) {
            return [sprintf('CSV record %d: Column J: If data is entered in Column J, Column M must contain capital S, C, or E', $record)];
          }

          // Column J: Perfection cannot precede the initially received date.
          if ($received_date !== NULL && $perfected_date < $received_date) {
            return [sprintf('CSV record %d: Column J: Date Perfected cannot be prior to Date Initially Received', $record)];
          }
        }

        // Column K: Date Completed is required when N contains a disposition.
        $completed = trim($columns[10]);
        if ($completed === '' && trim($columns[13]) !== '') {
          return [sprintf('CSV record %d: Column K: Must complete Column K if Disposition is listed in Column N', $record)];
        }

        // Column K: Otherwise optional, including for consultation rows.
        if ($completed !== '') {
          // Column K: Require a real date using the same format as I and J.
          if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $completed, $date_parts)
            || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
            return [sprintf('CSV record %d: Column K: Date Completed must be a valid date in MM/DD/YYYY format', $record)];
          }

          // Column K: Completion must fall within the report's fiscal year,
          // from October 1 of the previous year through September 30 inclusive.
          $completed_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
          if ($completed_date < ($fiscal_year - 1) * 10000 + 1001 || $completed_date > $fiscal_year * 10000 + 930) {
            return [sprintf('CSV record %d: Column K: Date Completed must fall within the fiscal year (10/01/%04d through 09/30/%04d)', $record, $fiscal_year - 1, $fiscal_year)];
          }

          // Column K: Completion cannot precede J when a perfected date exists.
          if ($perfected !== '' && $completed_date < $perfected_date) {
            return [sprintf('CSV record %d: Column K: Date Completed cannot be prior to Date Perfected', $record)];
          }
        }

        // Column L: Days Tolled is optional, but needs a perfected date in J.
        $days_tolled = trim($columns[11]);
        if ($days_tolled !== '') {
          if ($perfected === '') {
            return [sprintf('CSV record %d: Column L: Days Tolled only permitted if Perfected Date is entered', $record)];
          }

          // Column L: Accept non-negative whole days, including zero.
          if (!ctype_digit($days_tolled)) {
            return [sprintf('CSV record %d: Column L: Days Tolled must be a non-negative integer', $record)];
          }

          // Column L: When K is present, limit tolling to elapsed working days.
          // Exclude J, include K, and subtract weekends and supplied holidays.
          // With no completed date, the upper limit cannot yet be checked.
          if ($completed !== '' && (int) $days_tolled > $this->countWorkingDays($perfected, $completed)) {
            return [sprintf('CSV record %d: Column L: Days Tolled exceeds working days between Perfected and Completed Dates (Columns J and K)', $record)];
          }
        }

        // Column M: Track may be blank. The Column J check above already
        // requires uppercase S, C or E whenever a perfected date is present.
        // Independently, an uppercase G in S requires an uppercase E in M.
        if (trim($columns[18]) === 'G' && trim($columns[12]) !== 'E') {
          return [sprintf('CSV record %d: Column M: If Column S contains a G, Column M must contain an E', $record)];
        }

        // Column N: Completed non-consultation requests need a disposition.
        $disposition = trim($columns[13]);
        if ($completed !== '' && $consultation === 'N' && $disposition === '') {
          return [sprintf('CSV record %d: Column N: If Column K has Date Completed AND Column C = N (No), then Column N must have Disposition entered', $record)];
        }

        // Column N: Disposition code 12 requires explanatory data in O.
        if ($disposition === '12' && trim($columns[14]) === '') {
          return [sprintf('CSV record %d: Column N: Column O must contain data for Disposition Code 12', $record)];
        }

        // Column N: Codes 1, 2, 3, 4, 5 and 7 require a perfected date in J.
        if (in_array($disposition, ['1', '2', '3', '4', '5', '7'], TRUE) && $perfected === '') {
          return [sprintf('CSV record %d: Column N: If Disposition Code 1, 2, 3, 4, 5, or 7 is entered in Column N, Column J must have a Perfected Date', $record)];
        }

        // Column N: Codes 8 and 9 require the perfected date in J to be blank.
        if (in_array($disposition, ['8', '9'], TRUE) && $perfected !== '') {
          return [sprintf('CSV record %d: Column N: If Disposition Code 8 or 9 is listed in Column N, Column J must be blank for that request', $record)];
        }

        // Column O: An Other Reason requires disposition code 12 in N.
        // This is the reverse of the Column N check requiring O for code 12.
        if (trim($columns[14]) !== '' && $disposition !== '12') {
          return [sprintf('CSV record %d: Column O: If Column O contains information, Column N must contain Disposition Code 12', $record)];
        }

        // Column P: Optional alphanumeric exemptions must be comma-separated.
        // Allow whitespace around entries, but not in place of a comma.
        $applied_exemptions = trim($columns[15]);
        if ($applied_exemptions !== '' && !preg_match('/^[a-zA-Z0-9]+(?:\s*,\s*[a-zA-Z0-9]+)*$/', $applied_exemptions)) {
          return [sprintf('CSV record %d: Column P: Multiple exemptions must be separated with a comma (e.g., 3,5,7a,7c,7d)', $record)];
        }

        // Column P: Disposition code 3 requires at least one exemption.
        if ($disposition === '3' && $applied_exemptions === '') {
          return [sprintf('CSV record %d: Column P: Column P must contain exemption(s) for Disposition Code 3', $record)];
        }

        // Column P: Exemptions are allowed only for disposition codes 2 or 3.
        if ($applied_exemptions !== '' && !in_array($disposition, ['2', '3'], TRUE)) {
          return [sprintf('CSV record %d: Column P: Column N must list Disposition Code 2 or 3 if Column P contains exemptions', $record)];
        }

        // Column P: A complete exemption 3 entry requires statute data in E.
        // This complements the Column E check requiring exemption 3 in P.
        $exemptions = array_map('trim', explode(',', $applied_exemptions));
        if (in_array('3', $exemptions, TRUE) && $statutes === '') {
          return [sprintf("CSV record %d: Column P: If Column P contains a '3', Column E must contain information", $record)];
        }

        // Column Q: Request for EP - Date Received is optional.
        $ep_received = trim($columns[16]);
        if ($ep_received !== '') {
          // Column Q: Require a real date using the same format as I, J and K.
          if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $ep_received, $date_parts)
            || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
            return [sprintf('CSV record %d: Column Q: Request for EP - Date Received must be a valid date in MM/DD/YYYY format', $record)];
          }

          // Column Q: September 30 of the report year is the latest date.
          // Earlier years are allowed, as for Date Initially Received.
          $ep_received_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
          if ($ep_received_date > $fiscal_year * 10000 + 930) {
            return [sprintf('CSV record %d: Column Q: Request for EP - Date Received is later than the fiscal year', $record)];
          }
        }

        // Column Q: A received date is required when R or S has data.
        $ep_determined = trim($columns[17]);
        if ($ep_received === '' && ($ep_determined !== '' || trim($columns[18]) !== '')) {
          return [sprintf('CSV record %d: Column Q: Column Q must contain value if there is value in either Columns R or S', $record)];
        }

        if ($ep_determined !== '') {
          // Column R: Require a real date using the same format as Column Q.
          if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $ep_determined, $date_parts)
            || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
            return [sprintf('CSV record %d: Column R: Request for EP - Date of Determination must be a valid date in MM/DD/YYYY format', $record)];
          }

          // Column R: Require the report fiscal year, including its boundaries.
          $ep_determined_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
          if ($ep_determined_date < ($fiscal_year - 1) * 10000 + 1001 || $ep_determined_date > $fiscal_year * 10000 + 930) {
            return [sprintf('CSV record %d: Column R: Request for EP - Date of Determination is outside the fiscal year', $record)];
          }

          // Column R: Determination cannot precede a received date in Q.
          if ($ep_received !== '' && $ep_determined_date < $ep_received_date) {
            return [sprintf('CSV record %d: Column R: Request Expedited Processing - Date of Determination cannot be prior to Request Expedited Processing - Date Received', $record)];
          }
        }

        // Column S: A determination date in R requires uppercase G or D.
        if ($ep_determined !== '' && !in_array(trim($columns[18]), ['G', 'D'], TRUE)) {
          return [sprintf('CSV record %d: Column S: Must have G or D in Column S', $record)];
        }

        // Column T: Request for FW - Date Adjudication Began is optional.
        $fw_began = trim($columns[19]);
        if ($fw_began !== '') {
          // Column T: Require a real date using the same format as Column Q.
          if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $fw_began, $date_parts)
            || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
            return [sprintf('CSV record %d: Column T: Request for FW - Date Adjudication Began must be a valid date in MM/DD/YYYY format', $record)];
          }

          // Column T: September 30 of the report year is the latest date.
          // Earlier years are allowed, as for the other received dates.
          $fw_began_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
          if ($fw_began_date > $fiscal_year * 10000 + 930) {
            return [sprintf('CSV record %d: Column T: Request for FW - Date Adjudication Began is later than the fiscal year', $record)];
          }
        }

        // Column U: Request for FW - Date Adjudication Completed is optional.
        $fw_completed = trim($columns[20]);
        if ($fw_completed !== '') {
          // Column U: Require a real date using the same format as Column T.
          if (!preg_match('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-9]{4})$/', $fw_completed, $date_parts)
            || !checkdate((int) $date_parts[1], (int) $date_parts[2], (int) $date_parts[3])) {
            return [sprintf('CSV record %d: Column U: Request for FW - Date Adjudication Completed must be a valid date in MM/DD/YYYY format', $record)];
          }

          // Column U: Require the report fiscal year, including its boundaries.
          $fw_completed_date = (int) $date_parts[3] * 10000 + (int) $date_parts[1] * 100 + (int) $date_parts[2];
          if ($fw_completed_date < ($fiscal_year - 1) * 10000 + 1001 || $fw_completed_date > $fiscal_year * 10000 + 930) {
            return [sprintf('CSV record %d: Column U: Request for FW - Date Adjudication Completed is outside of the fiscal year', $record)];
          }

          // Column U: Completion cannot precede the start date in T, if given.
          if ($fw_began !== '' && $fw_completed_date < $fw_began_date) {
            return [sprintf('CSV record %d: Column U: Request for Fee Waiver - Date Adjudication Completed cannot be prior to Request for Fee Waiver - Date Adjudication Began', $record)];
          }
        }

        // Column V: A completion date in U requires uppercase G or D.
        if ($fw_completed !== '' && !in_array(trim($columns[21]), ['G', 'D'], TRUE)) {
          return [sprintf('CSV record %d: Column V: Must complete G or D in Column V', $record)];
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

  /**
   * Counts weekdays after the start date through the end date, minus holidays.
   *
   * Dates have already passed CSV validation. UTC avoids daylight-saving
   * effects. Whole weeks avoid iterating over every day of a long request.
   */
  private function countWorkingDays(string $start, string $end): int {
    return (new WorkingDays())->count($start, $end);
  }

}
