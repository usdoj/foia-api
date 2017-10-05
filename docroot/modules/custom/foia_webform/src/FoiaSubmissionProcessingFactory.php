<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\node\Entity\Node;

/**
 * Class FoiaSubmissionProcessingFactory.
 *
 * @package Drupal\foia_webform\Plugin\QueueWorker
 */
class FoiaSubmissionProcessingFactory {

  /**
   * Check the submission format and return the service.
   *
   * @param \Drupal\node\Entity\Node $agencyComponent
   *   The Agency Component node object.
   *
   * @return \Drupal\foia_webform\FoiaSubmissionServiceInterface
   *   Returns a Fo
   */
  public function get(Node $agencyComponent) {
    $submissionPreference = $agencyComponent->get('field_portal_submission_format');

    switch ($submissionPreference) {
      case 'api':
        $serviceName = 'foia_webform.foia_submission_api_service';
        break;

      case 'email':
        $serviceName = 'foia_webform.foia_submission_email_service';
        break;

      default:
        $serviceName = 'foia_webform.foia_submission_email_service';
        \Drupal::logger('foia_webform')
          ->notice('Invalid submission preference for component %title, defaulting to email.',
            [
              '%title' => $agencyComponent->label(),
              'link' => $agencyComponent->toLink($this->t('Edit Component'), 'edit-form')->toString(),
            ]
          );
    }

    return \Drupal::service($serviceName);
  }

}
