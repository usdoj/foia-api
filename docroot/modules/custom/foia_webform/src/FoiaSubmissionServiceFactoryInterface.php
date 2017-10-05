<?php

namespace Drupal\foia_webform;

use Drupal\node\NodeInterface;

/**
 * Provides an interface for submitting the request using the preferred method.
 */
interface FoiaSubmissionServiceFactoryInterface {

  /**
   * Determines which method to use for submitting the request.
   *
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The Agency Component node object.
   */
  public function get(NodeInterface $agencyComponent);

}
