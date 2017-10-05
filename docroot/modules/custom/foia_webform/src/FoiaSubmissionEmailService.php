<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\foia_webform\AgencyLookupServiceInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\node\Entity\Node;

/**
 * Class FoiaSubmissionEmailService.
 *
 * @package Drupal\foia_webform\Plugin\QueueWorker
 */
class FoiaSubmissionEmailService implements FoiaSubmissionServiceInterface {

  /**
   * Service to match the webform with the Agency Component.
   *
   * @var \Drupal\foia_webform\AgencyLookupService
   */
  protected $agencyLookUpService;

  /**
   * FoiaSubmissionEmailService constructor.
   *
   * @param \Drupal\foia_webform\Plugin\QueueWorker\AgencyLookupServiceInterface $agencyLookupService
   *   The Agency Lookup service.
   */
  public function __construct(AgencyLookupServiceInterface $agencyLookupService) {
    $this->agencyLookUpService = $agencyLookupService;
  }

  /**
   * Sends the email to the Agency Component.
   *
   * @param \Drupal\foia_webform\Plugin\QueueWorker\WebformSubmissionInterface $webformSubmission
   *   The FOIA form submission.
   * @param \Drupal\foia_webform\Plugin\QueueWorker\Node $agencyComponent
   *   The Agency Component node object.
   */
  public function sendSubmissionToComponent(WebformSubmissionInterface $webformSubmission, Node $agencyComponent) {

  }

}
