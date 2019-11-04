<?php

namespace Drupal\foia_webform;

use Drupal\foia_request\Entity\FoiaRequestInterface;

/**
 * Provides an interface defining the Request queueing.
 */
interface FoiaSubmissionQueueingServiceInterface {

  /**
   * Adds a FOIA request to the foia_submissions queue.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA Request to queue for later processing.
   */
  public function enqueue(FoiaRequestInterface $foiaRequest);

  /**
   * Checks to see if a FOIA request is in the foia_submissions queue.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA Request to look for in the queue.
   *
   * @return bool
   *   TRUE if the request is already queued, FALSE otherwise.
   */
  public function isQueued(FoiaRequestInterface $foiaRequest);

}
