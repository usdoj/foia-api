<?php

namespace Drupal\foia_webform;

use Drupal\webform\WebformInterface;

/**
 * File Attachment.
 */
class FileFieldLookupService {

  /**
   * {@inheritdoc}
   */
  public function getFileAttachmentElementsOnWebform(WebformInterface $webform) {
    $elements = $webform->getElementsInitialized();
    $fileAttachmentElementKeys = [];
    foreach ($elements as $key => $element) {
      if (isset($element['#type']) && $element['#type'] == 'managed_file') {
        $fileAttachmentElementKeys[] = $key;
      }
    }
    return $fileAttachmentElementKeys;
  }

}
