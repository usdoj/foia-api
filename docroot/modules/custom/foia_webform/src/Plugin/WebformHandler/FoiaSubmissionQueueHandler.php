<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformInterface;
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
      'field_webform_submission_website' => isset($_SERVER["HTTP_X_API_KEY"]) && $_SERVER["HTTP_X_API_KEY"] === 'mUPoczW5VDRQOvroK6srQIjEGc5xBP0KDHgE34fv' ? TRUE : FALSE,
      'field_agency_component' => [
        'target_id' => $agencyComponent->id(),
      ],
    ]);

    if ($this->fileAttachmentSubmitted($webformSubmission)) {
      $foiaRequest->setRequestStatus(FoiaRequestInterface::STATUS_SCAN);
    }

    $foiaRequest->save();
    return $foiaRequest;
  }

  /**
   * Determines whether or not any file attachments were submitted.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   *
   * @return bool
   *   TRUE if at least one attachment was submitted, otherwise FALSE.
   */
  protected function fileAttachmentSubmitted(WebformSubmissionInterface $webformSubmission) {
    $webform = $webformSubmission->getWebform();
    if ($webform->hasManagedFile()) {
      $fileAttachmentElementsOnWebform = $this->getFileAttachmentElementsOnWebform($webform);
      return $this->fileAttachmentsExist($fileAttachmentElementsOnWebform, $webformSubmission);
    }
    return FALSE;
  }

  /**
   * Gets the machine names of all file attachment elements on the webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform being submitted against.
   *
   * @return array
   *   Returns an array of machine names of file attachment elements on the
   *   webform being submitted against.
   */
  protected function getFileAttachmentElementsOnWebform(WebformInterface $webform) {
    $elements = $webform->getElementsInitialized();
    $fileAttachmentElementKeys = [];
    foreach ($elements as $key => $element) {
      if (isset($element['#type']) && $element['#type'] == 'managed_file') {
        $fileAttachmentElementKeys[] = $key;
      }
    }
    return $fileAttachmentElementKeys;
  }

  /**
   * Determines whether or not any file attachments were submitted.
   *
   * @param array $fileAttachmentElementKeys
   *   The machine names of all file attachment elements on the webform.
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   *
   * @return bool
   *   TRUE if at least one attachment was submitted, otherwise FALSE.
   */
  protected function fileAttachmentsExist(array $fileAttachmentElementKeys, WebformSubmissionInterface $webformSubmission) {
    foreach ($fileAttachmentElementKeys as $fileAttachmentElementKey) {
      if ($webformSubmission->getElementData($fileAttachmentElementKey)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Enqueue the FOIA Request if the status is STATUS_QUEUED.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA Request to be enqueued.
   */
  protected function queueFoiaRequest(FoiaRequestInterface $foiaRequest) {
    if ($foiaRequest->getRequestStatus() === FoiaRequestInterface::STATUS_QUEUED) {
      \Drupal::service('foia_webform.foia_submission_queueing_service')
        ->enqueue($foiaRequest);
    }
  }

}
