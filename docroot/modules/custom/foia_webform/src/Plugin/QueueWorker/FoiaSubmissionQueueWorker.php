<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\Core\Annotation\QueueWorker;

/**
 * Provides functionality for working with the queued FOIA form submissions.
 *
 * @QueueWorker (
 *   id = "foia_submissions",
 *   title = @Translation("Manual FOIA Form submitter"),
 * )
 */
class FoiaSubmissionQueueWorker extends QueueWorker {}
