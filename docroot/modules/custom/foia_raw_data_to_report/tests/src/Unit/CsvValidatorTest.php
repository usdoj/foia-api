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
   * Accepts quoted commas, quotes, multiline values, and blank lines.
   */
  public function testValidCsv(): void {
    $header = implode(',', array_fill(0, 29, 'column'));
    $row = $this->csvRow([0 => 'A, B', 1 => "First line\nSecond line", 22 => 'A "quote"']);
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
      [17 => '01/03/2026'],
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
   * Rejects every request-data column E through W when X contains data.
   */
  public function testAppealRequestDataExclusion(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    for ($column = 4; $column <= 22; $column++) {
      $overrides = [8 => '', 23 => '01/02/2026'];
      // Zero is data too, even though PHP treats it as an empty value.
      $overrides[$column] = '0';
      $this->assertSame(
        ['CSV record 2: If there is data in Column X, Columns E through W must be empty.'],
        $this->validateContents($header . $this->csvRow($overrides)),
        'Column index ' . $column,
      );
    }
  }

  /**
   * Allows blank I with or without X, and validates nonblank received dates.
   */
  public function testOptionalInitiallyReceived(): void {
    $header = implode(',', array_fill(0, 29, 'column')) . "\n";
    foreach ([
      [8 => ''],
      [8 => '', 23 => '01/02/2026'],
      [8 => '  ', 23 => '01/02/2026', 24 => '01/03/2026'],
      [8 => '', 9 => '01/02/2026', 12 => 'S'],
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
    $this->assertSame([], $this->validateContents($header . $first . $second));
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
   * Rejects empty input rather than silently producing an XML report.
   */
  public function testEmptyCsv(): void {
    foreach (['', "\n\r\n"] as $contents) {
      $this->assertStringContainsString('CSV file is empty', $this->validateContents($contents)[0]);
    }
  }

}
