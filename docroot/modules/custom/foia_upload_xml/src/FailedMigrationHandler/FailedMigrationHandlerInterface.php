<?php

namespace Drupal\foia_upload_xml\FailedMigrationHandler;

/**
 * Interface FailedMigrationHandlerInterface used to handle failed migrations.
 *
 * @package Drupal\foia_upload_xml\FailedMigrationHandler
 */
interface FailedMigrationHandlerInterface {

  /**
   * Handle an exception before it is rethrown.
   *
   * Useful to display more informative messaging or logging.
   */
  public function handle();

}
