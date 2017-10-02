<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

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
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class FoiaSubmissionQueueHandler extends EmailWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getWebform()->getSetting('results_disabled') ? WebformSubmissionInterface::STATE_COMPLETED : $webform_submission->getState();
    if (in_array($state, $this->configuration['states'])) {
      // Get the Webform generated email message.
      $message = $this->getMessage($webform_submission);
      // Add the submission to the queue.
      $this->queueSubmission($webform_submission, $message);
    }
  }

  /**
   * Adds the submission to the foia_form_manual_submitter queue.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission object.
   * @param array $message
   *   The email message array.
   */
  private function queueSubmission(WebformSubmissionInterface $webform_submission, array $message) {
    // @var @var QueueFactory $queue_factory
    $queue_factory = \Drupal::service('queue');

    // @var QueueInterface $queue
    $queue = $queue_factory->get('foia_form_manual_submitter');
    $submission = new \stdClass();
    $submission->sid = $webform_submission->id();;
    $submission->message = $message;

    // Log the form submission.
    \Drupal::logger('foia_webform')
      ->info('FOIA form submission added to queue.', ['link' => $webform_submission->toLink($this->t('View'))->toString()]);

    $queue->createItem($submission);
  }

}
