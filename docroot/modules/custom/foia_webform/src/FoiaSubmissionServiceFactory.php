<?php

namespace Drupal\foia_webform;

use Drupal\node\NodeInterface;

/**
 * Class FoiaSubmissionServiceFactory.
 */
class FoiaSubmissionServiceFactory {

  /**
   * {@inheritdoc}
   */
  public function get(NodeInterface $agencyComponent) {
    $submissionPreference = $agencyComponent->get('field_portal_submission_format');

    switch ($submissionPreference) {
      case 'api':
        $serviceName = 'foia_webform.foia_submission_api_service';
        break;

      default:
        \Drupal::logger('foia_webform')
          ->notice('Invalid submission preference for component %nid, defaulting to email.',
            [
              '%nid' => $agencyComponent->id(),
              'link' => $agencyComponent->toLink($this->t('Edit Component'), 'edit-form')->toString(),
            ]
          );
    }

    return \Drupal::service($serviceName);
  }

}
