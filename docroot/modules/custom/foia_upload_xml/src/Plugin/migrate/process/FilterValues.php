<?php

namespace Drupal\foia_upload_xml\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Combine each element of an array with a fixed set of values.
 *
 * Example:
 *
 * @code
 * process:
 *   values:
 *     plugin: foia_filter_values
 *     source:
 *       - key_1
 *       - key_2
 *       - key_3
 * @endcode
 *
 * The first (source) and second (filter) keys should be arrays of the same
 * length. Return the source array, selecting those elements for which the
 * filter array matches the third key (target).
 *
 * For example, if the first key is [1, 2, 3, 4], the second is [0, 1, 0, 1],
 * and the third is 1, then return [2, 4].
 *
 * As usual, the keys can be destination keys using the format '@dest_key'.
 *
 * @MigrateProcessPlugin(
 *   id = "foia_filter_values",
 *   handle_multiples = TRUE
 * )
 */
class FilterValues extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source = $value[0];
    $filter = $value[1];
    $target = $value[2];
    $result = [];
    foreach (array_keys($source) as $key) {
      if ($filter[$key] == $target) {
        $result[] = $source[$key];
      }
    }
    return $result;
  }

}
