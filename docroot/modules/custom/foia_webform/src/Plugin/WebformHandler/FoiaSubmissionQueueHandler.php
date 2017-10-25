<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_request\Entity\FoiaRequestInterface;
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
      $foiaRequest = $this->createFoiaRequest($webformSubmission);
      $this->queueFoiaRequest($foiaRequest);
    }
  }

  /**
   * Creates a FOIA Request entity that references the webform submission ID.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission to reference in the FOIA Request entity.
   *
   * @return \Drupal\foia_request\Entity\FoiaRequestInterface
   *   The created FOIA request entity.
   */
  protected function createFoiaRequest(WebformSubmissionInterface $webformSubmission) {
    $foiaRequest = FoiaRequest::create([
      'field_webform_submission_id' => $webformSubmission->id(),
    ]);

    $requesterEmailAddress = $webformSubmission->getElementData('email');
    if (isset($requesterEmailAddress)) {
      $foiaRequest->set('field_requester_email', $requesterEmailAddress);
    }

    $webformId = $webformSubmission->getWebform()->id();
    /** @var \Drupal\foia_webform\AgencyLookupServiceInterface $agencyLookupService */
    $agencyLookupService = \Drupal::service('foia_webform.agency_lookup_service');
    $agencyComponent = $agencyLookupService->getComponentFromWebform($webformId);
    $foiaRequest->field_agency_component->target_id = $agencyComponent->id();

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
    // @var QueueFactory $queueFactory
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
