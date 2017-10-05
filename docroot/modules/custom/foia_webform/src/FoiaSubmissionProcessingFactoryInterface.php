<?php

namespace Drupal\foia_webform;

use Drupal\node\Entity\Node;

/**
 * Provides an interface for submitting the request using the proper method.
 */
interface FoiaSubmissionProcessingFactoryInterface {

  /**
   * Determines which method to use for submitting the request.
   *
   * @param \Drupal\node\Entity\Node $agencyComponent
   *   The Agency Component node object.
   */
  public function get(Node $agencyComponent);

}
