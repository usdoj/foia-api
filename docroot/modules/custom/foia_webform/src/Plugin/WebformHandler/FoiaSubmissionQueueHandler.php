<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "foia_submission_queue",
 *   label = @Translation("FOIA Submission Queue"),
 *   category = @Translation("Queueing"),
 *   description = @Translation("Queues a webform submission to be sent later."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class FoiaSubmissionQueueHandler extends EmailWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webformSubmission, $update = TRUE) {
    $state = $webformSubmission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webformSubmission->getState();
    if (in_array($state, $this->configuration['states'])) {
      $componentAssociatedToWebform = $this->getComponentAssociatedToWebform($webformSubmission);
      if ($componentAssociatedToWebform) {
        $foiaRequest = $this->createFoiaRequest($webformSubmission, $componentAssociatedToWebform);
        $this->queueFoiaRequest($foiaRequest);
      }
    }
  }

  /**
   * Gets the agency component associated to the webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The agency component the webform is associated to, otherwise null if it
   *   is not associated to a component.
   */
  protected function getComponentAssociatedToWebform(WebformSubmissionInterface $webformSubmission) {
    $webformId = $webformSubmission->getWebform()->id();
    /** @var \Drupal\foia_webform\AgencyLookupServiceInterface $agencyLookupService */
    $agencyLookupService = \Drupal::service('foia_webform.agency_lookup_service');
    return $agencyLookupService->getComponentFromWebform($webformId);
  }

  /**
   * Creates a FOIA Request entity that references the webform submission ID.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission to reference in the FOIA Request entity.
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The agency component the request will be submitted to.
   *
   * @return \Drupal\foia_request\Entity\FoiaRequest
   *   A FOIA Request that references the webform submission and agency
   *   component being submitted to.
   */
  protected function createFoiaRequest(WebformSubmissionInterface $webformSubmission, NodeInterface $agencyComponent) {
    $foiaRequest = FoiaRequest::create([
      'field_webform_submission_id' => $webformSubmission->id(),
      'field_agency_component' => [
        'target_id' => $agencyComponent->id(),
      ],
    ]);

    $requesterEmailAddress = $webformSubmission->getElementData('email');
    if ($requesterEmailAddress) {
      $foiaRequest->set('field_requester_email', $requesterEmailAddress);
    }

    $fileAttachment = $webformSubmission->getElementData('attachments_supporting_documentation');
    if ($fileAttachment) {
      $foiaRequest->setRequestStatus(FoiaRequestInterface::STATUS_SCAN);
    }

    $foiaRequest->save();
    return $foiaRequest;
  }

  /**
   * Adds the FOIA request to the foia_submissions queue.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA Request to queue for later processing.
   */
  protected function queueFoiaRequest(FoiaRequestInterface $foiaRequest) {
    /** @var \Drupal\Core\Queue\QueueFactory $queueFactory */
    $queueFactory = \Drupal::service('queue');

    // @var QueueInterface $queue
    $foiaSubmissionsQueue = $queueFactory->get('foia_submissions');
    $submission = new \stdClass();
    $submission->id = $foiaRequest->id();

    // Log the form submission.
    \Drupal::logger('foia_webform')
      ->info('FOIA request #%request_id added to queue.',
        [
          '%request_id' => $foiaRequest->id(),
          'link' => $foiaRequest->toLink($this->t('View'))->toString(),
        ]
      );

    $foiaSubmissionsQueue->createItem($submission);
  }

}
