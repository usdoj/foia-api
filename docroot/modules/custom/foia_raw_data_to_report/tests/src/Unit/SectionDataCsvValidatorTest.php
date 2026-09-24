<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\SectionDataCsvValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks the Section IX-XI template and numeric values.
 */
#[CoversClass(SectionDataCsvValidator::class)]
#[Group('foia_raw_data_to_report')]
class SectionDataCsvValidatorTest extends UnitTestCase {

  /**
   * Validates temporary CSV contents.
   */
  private function validateCsv(array $rows, bool $bom = FALSE): array {
    $path = tempnam(sys_get_temp_dir(), 'section-csv-');
    try {
      $stream = fopen($path, 'wb');
      if ($bom) {
        fwrite($stream, "\xEF\xBB\xBF");
      }
      foreach ($rows as $row) {
        fputcsv($stream, $row, ',', '"', '');
      }
      fclose($stream);
      return (new SectionDataCsvValidator())->validate($path);
    }
    finally {
      unlink($path);
    }
  }

  /**
   * Accepts the sample, zero values, signs, whitespace, BOM, and blank lines.
   */
  public function testValidData(): void {
    $row = [39, '1.85', 7164103, 1918485, 0, 0, 145, 823];
    $this->assertSame([], $this->validateCsv([SectionDataCsvValidator::HEADERS, $row], TRUE));
    $row = ['-1', ' 0 ', '+1', 0, 0, 0, 0, 0];
    $this->assertSame([], $this->validateCsv([[], SectionDataCsvValidator::HEADERS, $row, []]));
  }

  /**
   * Requires exact headers and exactly one complete data record.
   */
  public function testStructure(): void {
    $headers = SectionDataCsvValidator::HEADERS;
    $this->assertNotEmpty($this->validateCsv([]));
    $this->assertStringContainsString('found 0', implode(' ', $this->validateCsv([$headers])));
    $row = array_fill(0, 8, '0');
    $this->assertStringContainsString('found 2', implode(' ', $this->validateCsv([$headers, $row, $row])));
    $errors = $this->validateCsv([$headers, array_fill(0, 7, '0')]);
    $this->assertStringContainsString('record 2 has 7 columns', implode(' ', $errors));
    $headers[0] .= ' ';
    $headers[1] = 'equivalent full-time employees';
    $errors = $this->validateCsv([$headers, $row]);
    $this->assertCount(2, $errors);
    $this->assertStringContainsString('record 1: Column A: Header', $errors[0]);
    $this->assertStringContainsString('record 1: Column B: Header', $errors[1]);
  }

  /**
   * Reports all invalid numeric cells without converting or rounding them.
   */
  public function testNumbers(): void {
    $row = ['1.5', '1e3', '', '1,000', '$2', 'NaN', '  ', '2.0'];
    $errors = $this->validateCsv([SectionDataCsvValidator::HEADERS, $row]);
    $this->assertCount(8, $errors);
    $this->assertStringContainsString('record 2: Column B: Equivalent Full-Time Employees must be a decimal number', $errors[1]);
    foreach ([0, 2, 3, 4, 5, 6, 7] as $index) {
      $this->assertStringContainsString('must be an integer', $errors[$index]);
    }
  }

}
