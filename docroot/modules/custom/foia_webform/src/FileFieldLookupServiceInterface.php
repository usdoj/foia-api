<?php

namespace Drupal\foia_webform;

use Drupal\webform\WebformInterface;

/**
 * Provides interface defining the File Field Lookup Service.
 */
interface FileFieldLookupServiceInterface {

  /**
   * Gets the machine names of all file attachment elements on the webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform being submitted against.
   *
   * @return array
   *   Returns an array of machine names of file attachment elements on the
   *   webform being submitted against.
   */
  public function getFileAttachmentElementsOnWebform(WebformInterface $webform);

}
