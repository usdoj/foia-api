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
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {
    // @var @var QueueFactory $queue_factory
    $queue_factory = \Drupal::service('queue');

    // @var QueueInterface $queue
    $queue = $queue_factory->get('foia_queue_submissions');
    $submission = new \stdClass();
    $submission->sid = $webform_submission->id();;
    $submission->message = $message;

    // Log the form submission.
    \Drupal::logger('foia_webform')
      ->info('FOIA form submission added to queue.', ['link' => $webform_submission->toLink($this->t('View'))->toString()]);

    $queue->createItem($submission);
  }

}
