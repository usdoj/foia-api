<?php

namespace Drupal\foia_request\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for FOIA Request entities.
 */
class FoiaRequestViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
