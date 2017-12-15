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
  public function addRequestToQueue(FoiaRequestInterface $foiaRequest);

}
