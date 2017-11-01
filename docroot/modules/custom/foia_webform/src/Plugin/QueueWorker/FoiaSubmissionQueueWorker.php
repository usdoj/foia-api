<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\foia_webform\FoiaSubmissionServiceFactoryInterface;
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
    $foiaRequest = FoiaRequest::load($data->id);

    // Check the submission preference for the Agency Component.
    $agencyComponentId = $foiaRequest->get('field_agency_component')->target_id;
    $agencyComponent = Node::load($agencyComponentId);
    $submissionService = $this->foiaSubmissionServiceFactory->get($agencyComponent);

    $foiaRequest->set('field_submission_time', REQUEST_TIME);
    // Submit the form values to the Agency Component.
    $validSubmissionResponse = $submissionService->sendRequestToComponent($foiaRequest, $agencyComponent);

    if ($validSubmissionResponse) {
      $foiaRequest->setRequestStatus(FoiaRequestInterface::STATUS_SUBMITTED);
      $submissionMethod = isset($validSubmissionResponse['type']) ? $validSubmissionResponse['type'] : '';
      $responseCode = isset($validSubmissionResponse['response_code']) ? $validSubmissionResponse['response_code'] : '';
      $caseManagementId = isset($validSubmissionResponse['id']) ? $validSubmissionResponse['id'] : '';
      $caseManagementStatusTrackingNumber = isset($validSubmissionResponse['status_tracking_number']) ? $validSubmissionResponse['status_tracking_number'] : '';
      if ($caseManagementId) {
        $foiaRequest->set('field_case_management_id', $caseManagementId);
      }
      if ($caseManagementStatusTrackingNumber) {
        $foiaRequest->set('field_tracking_number', $caseManagementStatusTrackingNumber);
      }
      $webformSubmissionId = $foiaRequest->get('field_webform_submission_id')->value;
      $webformSubmission = WebformSubmission::load($webformSubmissionId);
      $webformSubmission->delete();
    }
    else {
      $foiaRequest->setRequestStatus(FoiaRequestInterface::STATUS_FAILED);
      $invalidSubmissionInfo = $submissionService->getSubmissionErrors();
      $submissionMethod = isset($invalidSubmissionInfo['type']) ? $invalidSubmissionInfo['type'] : '';
      $responseCode = isset($invalidSubmissionInfo['response_code']) ? $invalidSubmissionInfo['response_code'] : '';
      $errorCode = isset($invalidSubmissionInfo['error_code']) ? $invalidSubmissionInfo['error_code'] : '';
      $errorMessage = isset($invalidSubmissionInfo['message']) ? $invalidSubmissionInfo['message'] : '';
      $errorDescription = isset($invalidSubmissionInfo['description']) ? $invalidSubmissionInfo['description'] : '';
      if ($errorCode) {
        $foiaRequest->set('field_error_code', $errorCode);
      }
      // @todo create separate error message and description fields
      if ($errorMessage || $errorDescription) {
        $foiaRequest->set('field_error_message', "Message: {$errorMessage}. Description: {$errorDescription}");
      }
    }
    $foiaRequest->setSubmissionMethod($submissionMethod);
    $foiaRequest->set('field_response_code', $responseCode);
    $foiaRequest->save();
  }

}
