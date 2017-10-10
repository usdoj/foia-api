<?php

namespace Drupal\foia_webform;

use Drupal\node\NodeInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Class FoiaSubmissionServiceEmail.
 */
class FoiaSubmissionServiceEmail implements FoiaSubmissionServiceInterface {

  /**
   * Submission-related error messages.
   *
   * @var array
   */
  protected $errors;

  /**
   * {@inheritdoc}
   */
  public function sendSubmissionToComponent(WebformSubmissionInterface $webformSubmission, WebformInterface $webform, NodeInterface $agencyComponent) {

  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionErrors() {
    $submissionErrors = $this->errors;
    $submissionErrors['type'] = 'email';
    return $submissionErrors;
  }

}
