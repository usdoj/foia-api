<?php

namespace Drupal\foia_webform;

use Drupal\node\NodeInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Class FoiaSubmissionServiceEmail.
 */
class FoiaSubmissionServiceEmail implements FoiaSubmissionServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function sendSubmissionToComponent(WebformSubmissionInterface $webformSubmission, NodeInterface $agencyComponent) {

  }

}
