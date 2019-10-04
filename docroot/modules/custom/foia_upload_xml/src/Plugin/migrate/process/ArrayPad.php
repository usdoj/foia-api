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
 *   sourceids:
 *     plugin: foia_array_pad
 *     source: some_key
 *     prefix:
 *       - key1
 *       - key2
 * @endcode
 *
 * Suppose the source values some_key, key1, key2 are equal to ['x', 'y', 'z'],
 * 'val1', and 'val2'. Then this plugin will assign
 * [['val1', 'val2', 'x'], ['val1', 'val2', 'y'], ['val1', 'val2', 'z']]
 * to the destination field sourceids.
 *
 * @MigrateProcessPlugin(
 *   id = "foia_array_pad",
 *   handle_multiples = TRUE
 * )
 */
class ArrayPad extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $prefix_keys = $this->configuration['prefix'];
    if (!is_array($prefix_keys)) {
      $prefix_keys = [$prefix_keys];
    }

    $prefix = [];
    foreach ($prefix_keys as $key) {
      $prefix[] = $row->get($key);
    }

    if (!is_array($value)) {
      $value = [$value];
    }

    $result = [];
    foreach ($value as $item) {
      $result[] = array_merge($prefix, [$item]);
    }

    return $result;
  }

}
