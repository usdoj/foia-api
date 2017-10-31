<?php

namespace Drupal\foia_webform;

use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Class FoiaSubmissionServiceFactory.
 */
class FoiaSubmissionServiceFactory implements FoiaSubmissionServiceFactoryInterface {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * FoiaSubmissionServiceFactory constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function get(NodeInterface $agencyComponent) {
    $submissionPreference = $agencyComponent->get('field_portal_submission_format')->value;

    switch ($submissionPreference) {
      case 'api':
        $serviceName = 'foia_webform.foia_submission_service_api';
        break;
      case 'email':
        $serviceName = 'foia_webform.foia_submission_service_email';
        break;
      default:
        $serviceName = 'foia_webform.foia_submission_service_email';
        $this->logger
          ->notice('Invalid or missing submission preference for component #%nid, defaulting to email.',
            [
              '%nid' => $agencyComponent->id(),
              'link' => $agencyComponent->toLink(t('Edit Component'), 'edit-form')->toString(),
            ]
          );
    }

    return \Drupal::service($serviceName);
  }

}
