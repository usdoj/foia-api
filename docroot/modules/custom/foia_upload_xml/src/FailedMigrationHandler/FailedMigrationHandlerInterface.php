<?php

namespace Drupal\foia_upload_xml\FailedMigrationHandler;

/**
 * Interface FailedMigrationHandlerInterface.
 *
 * It handles the failed migration.
 */
interface FailedMigrationHandlerInterface {

  /**
   * Handle an exception before it is rethrown.
   *
   * Useful to display more informative messaging or logging.
   */
  public function handle();

}
