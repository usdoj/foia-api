<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Parses double-quoted lists inside an already decoded request CSV cell.
 */
final class OtherStatuteValues {

  /**
   * Returns trimmed values, or NULL for a malformed quoted list.
   *
   * Embedded double quotes use CSV escaping (two consecutive double quotes).
   */
  public static function parse(string $value): ?array {
    if (!preg_match('/^\s*"(?:[^"]|"")*"\s*(?:,\s*"(?:[^"]|"")*"\s*)*$/s', $value)) {
      return NULL;
    }
    return array_map('trim', str_getcsv(trim($value), ',', '"', ''));
  }

}
