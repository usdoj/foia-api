<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\CsvValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests streaming validation of CSV column counts.
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
      return (new CsvValidator())->validate($path);
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
    $row = '"A, B","First line' . "\n" . 'Second line","A ""quote""",' . implode(',', array_fill(0, 26, 'value'));
    $this->assertSame([], $this->validateContents("\xEF\xBB\xBF" . $header . "\r\n\r\n" . $row . "\r\n"));
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
