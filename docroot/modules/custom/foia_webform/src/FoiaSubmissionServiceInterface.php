<?php

namespace Drupal\foia_webform;

use Drupal\node\NodeInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an interface for submitting submissions to the Components.
 */
interface FoiaSubmissionServiceInterface {

  /**
   * Determine if submission has scheduled email for specified handler.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\webform\WebformInterface $webform
   *   The Webform node object.
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The Agency Component node object.
   */
  public function sendSubmissionToComponent(WebformSubmissionInterface $webform_submission, WebformInterface $webform, NodeInterface $agencyComponent);

}
