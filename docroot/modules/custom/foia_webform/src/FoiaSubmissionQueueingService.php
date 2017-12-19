<?php

namespace Drupal\foia_webform;

use Drupal\foia_request\Entity\FoiaRequestInterface;

/**
 * Class FoiaSubmissionQueueingService.
 */
class FoiaSubmissionQueueingService implements FoiaSubmissionQueueingServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function addRequestToQueue(FoiaRequestInterface $foiaRequest) {
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
          'link' => $foiaRequest->toLink(t('View'))->toString(),
        ]
      );

    $foiaSubmissionsQueue->createItem($submission);
  }

}
