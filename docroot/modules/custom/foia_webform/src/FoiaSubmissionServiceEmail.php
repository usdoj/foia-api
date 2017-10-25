<?php

namespace Drupal\foia_webform;

use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\node\NodeInterface;

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
  public function sendRequestToComponent(FoiaRequestInterface $foiaRequest, NodeInterface $agencyComponent) {

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
