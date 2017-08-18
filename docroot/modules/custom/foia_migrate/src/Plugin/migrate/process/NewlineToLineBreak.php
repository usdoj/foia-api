<?php

namespace Drupal\foia_migrate\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin inserts HTML line breaks before all newlines in a string.
 *
 * @MigrateProcessPlugin(
 *   id = "nl2br"
 * )
 *
 * Used to insert HTML line breaks before newlines in a string.
 *
 * Available configuration keys:
 * - source: The source string.
 * - is_xhtml: (optional) When this boolean is TRUE, XHTML compatible line
 *   breaks will be used - e.g. <br />. If FALSE is passed, <br> will be used.
 *   Defaults to TRUE.
 *
 * Example:
 * @code
 * process:
 *   bar:
 *     plugin: nl2br
 *     source: foo
 * @endcode
 * If foo is "foo isn't\n bar", then bar will be "foo isn't<br /> bar".
 *
 * @see nl2br()
 */
class NewlineToLineBreak extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Check if the incoming value can cast to a string.
    $original = $value;
    if (!is_string($original) && ($original != ($value = @strval($value)))) {
      throw new MigrateException(sprintf('%s cannot be casted to a string', var_export($original, TRUE)));
    }

    $is_xhtml = isset($this->configuration['is_xhtml']) ? $this->configuration['is_xhtml'] : TRUE;

    return nl2br($value, $is_xhtml);
  }

}
