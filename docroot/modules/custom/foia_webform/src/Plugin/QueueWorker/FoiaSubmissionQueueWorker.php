<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\foia_webform\FoiaSubmissionServiceFactoryInterface;
use Drupal\foia_webform\FoiaSubmissionServiceInterface;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides functionality for working with the queued FOIA form submissions.
 *
 * @QueueWorker (
 *   id = "foia_submissions",
 *   title = @Translation("FOIA Submission Queue Worker"),
 * )
 */
class FoiaSubmissionQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The factory class to build the submission.
   *
   * @var \Drupal\foia_webform\FoiaSubmissionServiceFactoryInterface
   */
  protected $foiaSubmissionServiceFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(FoiaSubmissionServiceFactoryInterface $foiaSubmissionServiceFactory) {
    $this->foiaSubmissionServiceFactory = $foiaSubmissionServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('foia_webform.foia_submission_service_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    /** @var \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest */
    $foiaRequest = FoiaRequest::load($data->id);

    // Check the submission preference for the Agency Component.
    $agencyComponentId = $foiaRequest->get('field_agency_component')->target_id;
    $agencyComponent = Node::load($agencyComponentId);
    $submissionService = $this->foiaSubmissionServiceFactory->get($agencyComponent);

    $foiaRequest->set('field_submission_time', REQUEST_TIME);
    // Submit the form values to the Agency Component.
    $submissionResponse = $submissionService->sendRequestToComponent($foiaRequest, $agencyComponent);
    $this->handleSubmissionResponse($foiaRequest, $submissionResponse, $submissionService);
  }

  /**
   * Updates the FOIA request depending on successful or failed submission.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request sent off to the agency component.
   * @param array|bool $submissionResponse
   *   The response received when sending the request.
   * @param \Drupal\foia_webform\FoiaSubmissionServiceInterface $submissionService
   *   The submission service used to submit the request.
   */
  protected function handleSubmissionResponse(FoiaRequestInterface $foiaRequest, $submissionResponse, FoiaSubmissionServiceInterface $submissionService) {
    if ($submissionResponse) {
      $this->handleValidSubmission($foiaRequest, $submissionResponse);
    }
    else {
      $submissionResponse = $submissionService->getSubmissionErrors();
      $this->handleFailedSubmission($foiaRequest, $submissionResponse);
    }
    $submissionMethod = isset($submissionResponse['type']) ? $submissionResponse['type'] : '';
    $responseCode = isset($submissionResponse['response_code']) ? $submissionResponse['response_code'] : '';
    $foiaRequest->setSubmissionMethod($submissionMethod);
    if ($responseCode) {
      $foiaRequest->set('field_response_code', $responseCode);
    }
    $foiaRequest->save();
  }

  /**
   * Handles FOIA requests after successfully submitting them to components.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request sent off to the agency component.
   * @param array $validSubmissionResponse
   *   An array of valid submission response info.
   */
  protected function handleValidSubmission(FoiaRequestInterface $foiaRequest, array $validSubmissionResponse) {
    $newStatus = FoiaRequestInterace::STATUS_SUBMITTED;
    if ($foiaRequest->getSubmissionMethod() == FoiaRequestInterface::METHOD_EMAIL) {
      $newStatus = FoiaRequestInterface::STATUS_IN_TRANSIT;
    }
    $foiaRequest->setRequestStatus($newStatus);

    $caseManagementId = isset($validSubmissionResponse['id']) ? $validSubmissionResponse['id'] : '';
    $caseManagementStatusTrackingNumber = isset($validSubmissionResponse['status_tracking_number']) ? $validSubmissionResponse['status_tracking_number'] : '';
    if ($caseManagementId) {
      $foiaRequest->set('field_case_management_id', $caseManagementId);
    }
    if ($caseManagementStatusTrackingNumber) {
      $foiaRequest->set('field_tracking_number', $caseManagementStatusTrackingNumber);
    }

    // Only delete the webform submission if it is not "email in transit".
    if ($newStatus != FoiaRequestInterface::STATUS_IN_TRANSIT) {
      $this->deleteWebformSubmission($foiaRequest);
    }
  }

  /**
   * Handles FOIA requests after failed submission attempts to components.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request sent off to the agency component.
   * @param array $failedSubmissionInfo
   *   An array of failed submission response info.
   */
  protected function handleFailedSubmission(FoiaRequestInterface $foiaRequest, array $failedSubmissionInfo) {
    $foiaRequest->setRequestStatus(FoiaRequestInterface::STATUS_FAILED);

    $errorCode = isset($failedSubmissionInfo['code']) ? $failedSubmissionInfo['code'] : '';
    $errorMessage = isset($failedSubmissionInfo['message']) ? $failedSubmissionInfo['message'] : '';
    $errorDescription = isset($failedSubmissionInfo['description']) ? $failedSubmissionInfo['description'] : '';
    if ($errorCode) {
      $foiaRequest->set('field_error_code', $errorCode);
    }
    if ($errorMessage) {
      $foiaRequest->set('field_error_message', $errorMessage);
    }
    if ($errorDescription) {
      $foiaRequest->set('field_error_description', $errorDescription);
    }
  }

  /**
   * Deletes the webform submission associated to the given FOIA request.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request sent off to the agency component.
   */
  protected function deleteWebformSubmission(FoiaRequestInterface $foiaRequest) {
    $webformSubmissionId = $foiaRequest->get('field_webform_submission_id')->value;
    $webformSubmission = WebformSubmission::load($webformSubmissionId);
    $webformSubmission->delete();
  }

}
