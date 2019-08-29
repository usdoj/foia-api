<?php

namespace Drupal\foia_webform;

use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\node\NodeInterface;

/**
 * Provides an interface for submitting submissions to the Components.
 */
interface FoiaSubmissionServiceInterface {

  const VERSION = '1.1.0';

  /**
   * Sends a FOIA request to an agency component.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request to send.
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The Agency Component node.
   *
   * @return array|bool
   *   On successful submissions returns an associative array with id and
   *   status_tracking_number, otherwise returns FALSE.
   */
  public function sendRequestToComponent(FoiaRequestInterface $foiaRequest, NodeInterface $agencyComponent);

  /**
   * Returns submission-related error messages.
   *
   * @return array
   *   Array of submission-related error messages.
   */
  public function getSubmissionErrors();

}
