<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\CsvValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests CSV structure and expedited processing field dependencies.
 */
#[CoversClass(CsvValidator::class)]
#[Group('foia_raw_data_to_report')]
class CsvValidatorTest extends UnitTestCase {

  /**
   * Rejects nonblank disposition codes outside the single integer range.
   */
  public function testDispositionRange(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $error = 'CSV record 2: Column N (Disposition Reason) must be an integer from 1 to 12';
    foreach (['0', '13', '-1', '1.5', '1.0', '1e1', 'Other', '1,2', '01', '+1'] as $value) {
      $errors = $this->validateContents($header . $this->csvRow([10 => '01/02/2026', 13 => $value]));
      $this->assertSame([$error], $errors);
    }
    foreach (range(1, 12) as $code) {
      $row = [10 => '01/02/2026', 13 => ' ' . $code . ' '];
      if (in_array($code, [1, 2, 3, 4, 5, 7], TRUE)) {
        $row += [9 => '01/01/2026', 12 => 'S'];
      }
      if ($code === 3) {
        $row[15] = '6';
      }
      if ($code === 12) {
        $row[14] = 'Other reason';
      }
      $this->assertSame([], $this->validateContents($header . $this->csvRow($row)));
    }
    foreach (['', '   '] as $value) {
      $this->assertSame([], $this->validateContents($header . $this->csvRow([13 => $value])));
    }
  }

  /**
   * Column Z accepts only integer codes, with whitespace ignored.
   */
  public function testAppealDispositionRange(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $base = [8 => '', 23 => '01/01/2026', 24 => '01/02/2026'];
    foreach (['0', '5', '-1', '1.0', '1.5', '1e0', '01', '+1', '1,2',
      'Affirmed on Appeal', 'Partially Affirmed & Partially Reversed/Remanded',
      'Completely Reversed/Remanded on Appeal', 'Appeal Closed for Other Reason',
    ] as $value) {
      $row = $this->csvRow($base + [25 => $value]);
      $this->assertSame(['CSV record 2: Column Z (Appeal Disposition) must be an integer from 1 to 4'], $this->validateContents($header . $row));
    }
    foreach (['', '   '] as $value) {
      $row = $this->csvRow([8 => '', 23 => '01/01/2026', 25 => $value]);
      $this->assertSame([], $this->validateContents($header . $row));
    }
  }

  /**
   * Restricts AA codes and requires a qualifying appeal disposition.
   */
  public function testAppealDenialCodes(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $base = [8 => '', 23 => '01/01/2026', 24 => '01/02/2026', 25 => '1'];
    foreach (['0', '12', '-1', '1.0', '01', '+1', 'No Records', '1;', '1,', '1,,2'] as $value) {
      $errors = $this->validateContents($header . $this->csvRow($base + [26 => $value]));
      $this->assertSame(['CSV record 2: Column AA (Reasons for Denial on Appeal) must contain comma-separated integers from 1 to 11'], $errors);
    }
    foreach (['1', '2', '4'] as $disposition) {
      $row = array_replace($base, [25 => $disposition, 26 => ' 1,2,3,4,5,6,7,8,9,10,11,11 ', 27 => 'Explanation']);
      $this->assertSame([], $this->validateContents($header . $this->csvRow($row)));
    }
    $row = array_replace($base, [25 => '3', 26 => '1']);
    $message = 'CSV record 2: If Column AA ( Reasons for Denial on Appeal ) has data, Column Z must have either 1, 2, or 4';
    $this->assertSame([$message], $this->validateContents($header . $this->csvRow($row)));
    $row = [8 => '', 23 => '01/01/2026', 26 => '1'];
    $this->assertSame([$message], $this->validateContents($header . $this->csvRow($row)));
  }

  /**
   * Notes coexist with errors, retain row context, and reset for each file.
   */
  public function testPartialGrantNotes(): void {
    $path = tempnam(sys_get_temp_dir(), 'csv-notes-');
    try {
      $header = implode(',', array_fill(0, 29, 'column')) . "\n";
      $base = [9 => '01/02/2026', 10 => '01/05/2026', 12 => 'S', 13 => '2'];
      $validator = new CsvValidator();
      $contents = $header . $this->csvRow($base);
      $contents .= $this->csvRow(array_replace($base, [1 => 'Request 2', 15 => '  ']));
      $contents .= $this->csvRow(array_replace($base, [1 => 'Request 3', 15 => '6']));
      file_put_contents($path, $contents);
      $result = $validator->validateWithNotes($path, 2026);
      $this->assertSame([], $result['errors']);
      $this->assertCount(2, $result['notes']);
      $message = 'Column P: Note: This request has be closed as Disposition 2 "Partial grant/partial denial," but does not have an exemption in Column P.  Please review this request to ensure this is correct."';
      $this->assertSame('CSV record 2: ' . $message, $result['notes'][0]);
      $this->assertSame('CSV record 3: ' . $message, $result['notes'][1]);
      $this->assertSame([], $validator->validate($path, 2026));
      file_put_contents($path, $header . $this->csvRow(array_replace($base, [0 => ''])));
      $result = $validator->validateWithNotes($path, 2026);
      $this->assertNotEmpty($result['errors']);
      $this->assertCount(1, $result['notes']);
      foreach (['1', '3'] as $disposition) {
        file_put_contents($path, $header . $this->csvRow(array_replace($base, [13 => $disposition])));
        $result = $validator->validateWithNotes($path, 2026);
        $this->assertSame([], $result['notes']);
        $this->assertSame($disposition === '3', $result['errors'] !== []);
      }
    }
    finally {
      unlink($path);
    }
  }

  /**
   * Validates temporary CSV contents and removes the fixture.
   */
  private function validateContents(string $contents): array {
    $path = tempnam(sys_get_temp_dir(), 'foia-csv-');
    try {
      file_put_contents($path, $contents);
      return (new CsvValidator())->validate($path, 2026);
    }
    finally {
      unlink($path);
    }
  }

  /**
   * Requires exactly one receipt date and checks appeal dates and year-end.
   */
  public function testAppealReceivedRequiredAndFiscalYear(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    foreach (['', '  '] as $value) {
      $row = $this->csvRow([8 => '  ', 23 => $value]);
      $this->assertSame(['CSV record 2: Column X: Appeal Date Received cannot be blank'], $this->validateContents($header . $row));
    }
    $this->assertSame([], $this->validateContents($header . $this->csvRow()));
    $this->assertSame([], $this->validateContents($header . $this->csvRow([2 => 'Y', 3 => ''])));
    $both = $this->validateContents($header . $this->csvRow([23 => '01/02/2026']));
    $this->assertCount(1, $both);
    $this->assertStringContainsString('If there is data in Column X, Columns E through W must be empty', $both[0]);
    foreach (['9/30/2026', '10/01/2025', '01/01/2020'] as $value) {
      $this->assertSame([], $this->validateContents($header . $this->csvRow([8 => '', 23 => $value])));
    }
    $rows = $this->csvRow([8 => '', 23 => '10/01/2026']);
    $rows .= $this->csvRow([1 => 'Request 2', 8 => '', 23 => '02/30/2026']);
    $rows .= $this->csvRow([1 => 'Request 3', 8 => '', 23 => '']);
    $this->assertSame([
      'CSV record 2: Column X: Appeal Date Received is later than the fiscal year',
      'CSV record 3: Column X: Appeal Date Received must be a valid date in MM/DD/YYYY format',
      'CSV record 4: Column X: Appeal Date Received cannot be blank',
    ], $this->validateContents($header . $rows));
  }

  /**
   * Checks appeal closure dates, dispositions, and independent row state.
   */
  public function testAppealClosedRules(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $base = [8 => '', 23 => '01/01/2025'];
    foreach (['10/01/2025', '09/30/2026'] as $date) {
      $row = $this->csvRow($base + [24 => $date, 25 => '1', 26 => '1']);
      $this->assertSame([], $this->validateContents($header . $row));
    }
    $cases = [
      [
        [24 => '09/30/2025', 25 => '1', 26 => '1'],
        'Appeal Date Closed is outside the fiscal year',
      ],
      [
        [24 => '10/01/2026', 25 => '1', 26 => '1'],
        'Appeal Date Closed is outside the fiscal year',
      ],
      [
        [24 => '01/02/2026'],
        'Appeal Disposition is required if Appeal Closed Date is entered ',
      ],
      [
        [24 => '  ', 25 => '1', 26 => '1'],
        'Appeal Closed Date is required if Appeal Disposition is entered',
      ],
      [
        [23 => '01/03/2026', 24 => '01/02/2026', 25 => '1', 26 => '1'],
        'Appeal Date Closed cannot be prior to Appeal Date Received',
      ],
      [
        [24 => '02/30/2026', 25 => '1', 26 => '1'],
        'Appeal Date Closed must be a valid date in MM/DD/YYYY format',
      ],
    ];
    foreach ($cases as [$values, $message]) {
      $row = $this->csvRow(array_replace($base, $values));
      $this->assertSame(['CSV record 2: Column Y: ' . $message], $this->validateContents($header . $row));
    }
    // A previous row's received date must not leak into a blank X comparison.
    $first = $this->csvRow([8 => '', 23 => '09/30/2026']);
    $second = $this->csvRow([1 => 'Request 2', 24 => '01/02/2026', 25 => '1', 26 => '1']);
    $this->assertSame([], $this->validateContents($header . $first . $second));
    $row = $this->csvRow([8 => '', 23 => '01/03/2026', 24 => '09/30/2025']);
    $this->assertCount(3, $this->validateContents($header . $row));
  }

  /**
   * Checks denial reasons and exemptions required by appeal dispositions.
   */
  public function testAppealDispositionReasons(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $base = [8 => '', 23 => '01/01/2026', 24 => '01/02/2026'];
    foreach (['1', '2'] as $label) {
      $row = $this->csvRow($base + [25 => $label, 26 => '  ', 28 => '']);
      $this->assertSame([
        'CSV record 2: Column Z: If Column Z is 1 or 2, there must be an entry in Column AA and/or Column AC',
      ], $this->validateContents($header . $row));
      foreach ([[26 => '1'], [28 => '3'], [26 => '1', 28 => '3']] as $values) {
        $row = $this->csvRow($base + [25 => ' ' . $label . ' '] + $values);
        $expected = [];
        $this->assertSame($expected, $this->validateContents($header . $row));
      }
    }
    $row = $this->csvRow($base + [25 => '4']);
    $this->assertSame([
      'CSV record 2: Column Z: If Column Z is 4, Column AA must have at least one reason entered',
    ], $this->validateContents($header . $row));
    $row = $this->csvRow($base + [25 => '4', 26 => '1']);
    $this->assertSame([], $this->validateContents($header . $row));
    $row = $this->csvRow($base + [25 => '3']);
    $this->assertSame([], $this->validateContents($header . $row));
  }

  /**
   * Requires explanation for the complete Other entry in appeal denial lists.
   */
  public function testAppealOtherReasonExplanation(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $base = [8 => '', 23 => '01/01/2026', 24 => '01/02/2026', 25 => '3'];
    foreach (['11', '1, 11', ' 11 ,11'] as $reasons) {
      foreach (['', '  '] as $explanation) {
        $row = $this->csvRow(array_replace($base, [25 => '1']) + [26 => $reasons, 27 => $explanation]);
        $this->assertSame([
          'CSV record 2: Column AB: If Column AA has code 11 (Other) entered, there must be a value in Column AB',
        ], $this->validateContents($header . $row));
      }
      $row = $this->csvRow(array_replace($base, [25 => '1']) + [26 => $reasons, 27 => 'Explanation']);
      $this->assertSame([], $this->validateContents($header . $row));
    }
    foreach (['', '1', '6'] as $reasons) {
      $row = $this->csvRow(array_replace($base, [25 => $reasons === '' ? '3' : '1']) + [26 => $reasons]);
      $this->assertSame([], $this->validateContents($header . $row));
    }
  }

  /**
   * Restricts appeal exemptions to disposition codes 1 and 2.
   */
  public function testAppealExemptionDisposition(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $base = [8 => '', 23 => '01/01/2026', 24 => '01/02/2026', 26 => '1'];
    foreach (['1', '2'] as $label) {
      $row = $this->csvRow($base + [25 => ' ' . $label . ' ', 28 => '3, 5']);
      $this->assertSame([], $this->validateContents($header . $row));
    }
    foreach (['4'] as $label) {
      $row = $this->csvRow($base + [25 => $label, 28 => '3']);
      $this->assertSame([
        'CSV record 2: Column AC: If AC has exemptions, Column Z must be 1 or 2',
      ], $this->validateContents($header . $row));
    }
    $row = $this->csvRow([8 => '', 23 => '01/01/2026', 28 => '0']);
    $this->assertSame([
      'CSV record 2: Column AC: If AC has exemptions, Column Z must be 1 or 2',
    ], $this->validateContents($header . $row));
    foreach (['', '  '] as $value) {
      $row = $this->csvRow([8 => '', 23 => '01/01/2026', 28 => $value]);
      $this->assertSame([], $this->validateContents($header . $row));
    }
  }

  /**
   * Accepts quoted commas, quotes, multiline values, and blank lines.
   */
  public function testValidCsv(): void {
    $header = implode(',', array_fill(0, 29, 'column'));
    $row = $this->csvRow([0 => 'A, B', 1 => "First line\nSecond line", 19 => '01/01/2026', 22 => 'A "quote"']);
    $this->assertSame([], $this->validateContents("\xEF\xBB\xBF" . $header . "\r\n\r\n" . $row . "\r\n"));
  }

  /**
   * Serializes a valid request row with selected column overrides.
   */
  private function csvRow(array $overrides = []): string {
    $row = array_fill(0, 29, '');
    $row[0] = 'Component';
    $row[1] = 'Request 1';
    $row[2] = 'N';
    $row[3] = '20';
    $row[8] = '01/01/2026';
    $stream = fopen('php://temp', 'w+');
    fputcsv($stream, array_replace($row, $overrides), ',', '"', '');
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);
    return $contents;
  }

  /**
   * Requires Q for R or S, but does not require R for Q, S, or T.
   */
  public function testExpeditedReceivedDependency(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    foreach ([
      [17 => '01/03/2026', 18 => 'D'],
      [18 => 'D'],
      [16 => '  ', 17 => '01/03/2026', 18 => 'D'],
    ] as $overrides) {
      $this->assertSame(
        ['CSV record 2: Column Q: Column Q must contain value if there is value in either Columns R or S'],
        $this->validateContents($header . $this->csvRow($overrides)),
      );
    }
    foreach ([
      [],
      [16 => '01/02/2026'],
      [19 => '01/02/2026'],
      [16 => '01/02/2026', 18 => 'D'],
      [16 => '01/02/2026', 17 => '01/03/2026', 18 => 'D'],
    ] as $overrides) {
      $this->assertSame([], $this->validateContents($header . $this->csvRow($overrides)));
    }
    // The existing R date-order and S granted/denied rules still apply.
    $row = $this->csvRow([16 => '01/03/2026', 17 => '01/02/2026', 18 => 'D']);
    $this->assertStringContainsString('cannot be prior', $this->validateContents($header . $row)[0]);
    $row = $this->csvRow([16 => '01/02/2026', 17 => '01/03/2026']);
    $this->assertSame(['CSV record 2: Column S: Must have G or D in Column S'], $this->validateContents($header . $row));
  }

  /**
   * Rejects E-P and T-W when X contains data, while allowing Q, R, and S.
   */
  public function testAppealRequestDataExclusion(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    foreach (array_merge(range(4, 15), range(19, 22)) as $column) {
      $overrides = [8 => '', 23 => '01/02/2026'];
      // Zero is data too, even though PHP treats it as an empty value.
      $overrides[$column] = '0';
      $this->assertContains(
        'CSV record 2: If there is data in Column X, Columns E through W must be empty, except for Columns Q, R, and S; those may optionally have data, but Columns E through P and Columns T through W must be blank.',
        $this->validateContents($header . $this->csvRow($overrides)),
        'Column index ' . $column,
      );
    }
  }

  /**
   * Allows expedited-processing data on appeal rows with its usual validation.
   */
  public function testAppealExpeditedProcessing(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $appeal = [8 => '', 23 => '01/02/2026'];
    foreach ([
      [],
      [16 => '01/03/2026'],
      [16 => '01/03/2026', 18 => 'D'],
      [16 => '01/03/2026', 17 => '01/04/2026', 18 => 'D'],
      [16 => '01/03/2026', 18 => 'G'],
      [16 => '01/03/2026', 17 => '01/04/2026', 18 => 'G'],
    ] as $values) {
      $this->assertSame([], $this->validateContents($header . $this->csvRow($appeal + $values)));
    }
    $cases = [
      [
        [18 => 'D'],
        'Column Q must contain value',
      ],
      [
        [16 => 'bad-date'],
        'Column Q: Request for EP - Date Received must be a valid date',
      ],
      [
        [16 => '01/03/2026', 17 => '01/02/2026', 18 => 'D'],
        'cannot be prior',
      ],
      [
        [16 => '01/03/2026', 17 => '01/04/2026'],
        'Must have G or D in Column S',
      ],
    ];
    foreach ($cases as [$values, $message]) {
      $errors = $this->validateContents($header . $this->csvRow($appeal + $values));
      $this->assertStringContainsString($message, $errors[0]);
    }
  }

  /**
   * Allows blank I with X, and validates nonblank received dates.
   */
  public function testOptionalInitiallyReceived(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    foreach ([
      [8 => '', 23 => '01/02/2026'],
      [8 => '  ', 23 => '01/02/2026', 24 => '01/03/2026', 25 => '1', 26 => '1'],
      [8 => '01/01/2026'],
    ] as $overrides) {
      $this->assertSame([], $this->validateContents($header . $this->csvRow($overrides)));
    }
    $this->assertSame(
      ['CSV record 2: Column I: Date Initially Received must be a valid date in MM/DD/YYYY format'],
      $this->validateContents($header . $this->csvRow([8 => '02/30/2026'])),
    );
    $this->assertSame(
      ['CSV record 2: Column I: Date Initially Received is later than the fiscal year'],
      $this->validateContents($header . $this->csvRow([8 => '10/01/2026'])),
    );
    // A blank I on the next row must not reuse the previous row's date.
    $first = $this->csvRow([8 => '09/01/2026']);
    $second = $this->csvRow([1 => 'Request 2', 8 => '', 9 => '01/02/2026', 12 => 'S']);
    $this->assertSame(['CSV record 3: Column X: Appeal Date Received cannot be blank'], $this->validateContents($header . $first . $second));
    $row = $this->csvRow([8 => '01/03/2026', 9 => '01/02/2026', 12 => 'S']);
    $this->assertSame(
      ['CSV record 2: Column J: Date Perfected cannot be prior to Date Initially Received'],
      $this->validateContents($header . $row),
    );
  }

  /**
   * Identifies Column S as the source of the expedited-track requirement.
   */
  public function testExpeditedTrackMessage(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $row = $this->csvRow([16 => '01/02/2026', 18 => 'G']);
    $this->assertSame(
      ['CSV record 2: Column M: If Column S contains a G, Column M must contain an E'],
      $this->validateContents($header . $row),
    );
  }

  /**
   * Rejects an incorrect header column count.
   */
  public function testInvalidHeader(): void {
    $errors = $this->validateContents(implode(',', array_fill(0, 28, 'column')));
    $this->assertStringContainsString('record 1 has 28 columns; expected 29', $errors[0]);
  }

  /**
   * Checks data rows, including extra trailing empty columns.
   */
  public function testInvalidDataRecord(): void {
    $header = implode(',', array_fill(0, 29, 'column'));
    $errors = $this->validateContents($header . "\n\n" . $header . ',');
    $this->assertStringContainsString('record 3 has 30 columns; expected 29', $errors[0]);
  }

  /**
   * Collects multiple failures per row and checks duplicates after errors.
   */
  public function testCollectsAllErrors(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $first = $this->csvRow([0 => '', 3 => '10']);
    $second = $this->csvRow([0 => '', 16 => '', 18 => 'D']);
    $this->assertSame([
      'CSV record 2: Column A: Component cannot be blank',
      'CSV record 2: Column D: Must complete Days Allowed with 20 or 30',
      'CSV record 3: Column A: Component cannot be blank',
      'CSV record 3: Column B: Request Number is duplicate',
      'CSV record 3: Column Q: Column Q must contain value if there is value in either Columns R or S',
    ], $this->validateContents($header . $first . $second));
  }

  /**
   * A malformed header or row must not suppress validation of later rows.
   */
  public function testContinuesAfterMalformedRecords(): void {
    $contents = "bad,header\nshort,row\n" . $this->csvRow([0 => '']);
    $errors = $this->validateContents($contents);
    $this->assertCount(3, $errors);
    $this->assertStringContainsString('record 1 has 2 columns', $errors[0]);
    $this->assertStringContainsString('record 2 has 2 columns', $errors[1]);
    $this->assertSame('CSV record 3: Column A: Component cannot be blank', $errors[2]);
  }

  /**
   * Invalid dates cannot cause calculations or leak dates between rows.
   */
  public function testInvalidDatesDoNotPreventOtherChecks(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    $valid = $this->csvRow([9 => '01/02/2026', 12 => 'S']);
    $invalid = $this->csvRow([
      1 => 'Request 2',
      8 => 'bad',
      9 => 'bad',
      10 => 'bad',
      11 => '1',
      12 => 'S',
      13 => '1',
      16 => 'bad',
      17 => 'bad',
      18 => 'D',
      19 => 'bad',
      20 => 'bad',
      21 => 'D',
    ]);
    $later = $this->csvRow([1 => 'Request 3', 0 => '']);
    $errors = $this->validateContents($header . $valid . $invalid . $later);
    $this->assertCount(8, $errors);
    foreach (array_slice($errors, 0, 7) as $error) {
      $this->assertStringContainsString('CSV record 3:', $error);
      $this->assertStringContainsString('must be a valid date', $error);
    }
    $this->assertSame('CSV record 4: Column A: Component cannot be blank', $errors[7]);
  }

  /**
   * Rejects empty input rather than silently producing an XML report.
   */
  public function testEmptyCsv(): void {
    foreach (['', "\n\r\n"] as $contents) {
      $this->assertStringContainsString('CSV file is empty', $this->validateContents($contents)[0]);
    }
  }

}
