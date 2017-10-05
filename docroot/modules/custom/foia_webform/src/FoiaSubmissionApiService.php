<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\foia_webform\AgencyLookupServiceInterface;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\ClientInterface;
use Drupal\node\Entity\Node;

/**
 * Class FoiaSubmissionApiService.
 *
 * @package Drupal\foia_webform\Plugin\QueueWorker
 */
class FoiaSubmissionApiService implements FoiaSubmissionServiceInterface {

  private $apiURL;

  /**
   * The Agency Lookup service.
   *
   * @var \Drupal\foia_webform\AgencyLookupService
   */
  protected $agencyLookUpService;

  /**
   * The HTTP client to make calls to the API with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * FoiaSubmissionApiService constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP Guzzle client.
   * @param \Drupal\foia_webform\AgencyLookupServiceInterface $agencyLookupService
   *   The Agency Lookup service.
   */
  public function __construct(ClientInterface $httpClient, AgencyLookupServiceInterface $agencyLookupService) {
    $this->httpClient = $httpClient;
    $this->agencyLookUpService = $agencyLookupService;
  }

  /**
   * Submits the submission via the API.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   * @param \Drupal\node\Entity\Node $agencyComponent
   *   The Agency Components node object.
   */
  public function sendSubmissionToComponent(WebformSubmissionInterface $webformSubmission, Node $agencyComponent) {
    $this->apiURL = $agencyComponent->get('field_submission_api');

    if ($this->apiURL) {
      $payload = $this->getSubmissionPayload($webformSubmission);
      $agencyInfo = $this->getAgencyInfo($agencyComponent);
    }
    else {
      \Drupal::logger('foia_webform')
        ->error('Unable to submit request via the API. Missing API Submission URL for %component',
          [
            '%component' => $agencyComponent->label(),
            'link' => $agencyComponent->toLink($this->t('Edit Component'), 'edit-form')->toString(),
          ]
        );
    }
  }

  /**
   * Parse the submission values into an array.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The Webform submission values.
   *
   * @return array
   *   Returns the submissions as an array.
   */
  private function getSubmissionPayload(WebformSubmissionInterface $webformSubmission) {
    return $webformSubmission->getData();
  }

  /**
   * Parse the Agency information from the Agency Component.
   *
   * @param \Drupal\node\Entity\Node $agencyComponent
   *   The Agency Component node object.
   */
  private function getAgencyInfo(Node $agencyComponent) {

  }

}
