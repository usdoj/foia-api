<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\node\Entity\Node;
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
   * @param \Drupal\node\Entity\Node $agencyComponent
   *   The Agency Component node object.
   *
   * @return bool
   *   TRUE if submission has scheduled email.
   */
  public function sendSubmissionToComponent(WebformSubmissionInterface $webform_submission, Node $agencyComponent);

}
