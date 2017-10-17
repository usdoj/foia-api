<?php

namespace Drupal\foia_request\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining FOIA Request entities.
 *
 * @ingroup foia_request
 */
interface FoiaRequestInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Request is in queue to be processed.
   */
  const QUEUED = 0;

  /**
   * Request has been submitted.
   */
  const SUBMITTED = 1;

  /**
   * Submission has failed.
   */
  const FAILED = 2;

  /**
   * Gets the status of the foia_request entity.
   *
   * @return int
   *   One of FoiaRequestInterface::QUEUED or
   *   FoiaRequestInterface::SUBMITTED or
   *   FoiaRequestInterface::FAILED
   */
  public function getStatus();

  /**
   * Sets the status of the foia_request entity.
   *
   * @param int $status
   *   Set to QUEUED to mark enqueued,
   *   SUBMITTED to mark submitted,
   *   FAILED to mark failure.
   *
   * @return \Drupal\foia_request\Entity\FoiaRequestInterface
   *   The called foia_request entity.
   */
  public function setStatus($status);

  /**
   * Gets the FOIA Request name.
   *
   * @return string
   *   Name of the FOIA Request.
   */
  public function getName();

  /**
   * Sets the FOIA Request name.
   *
   * @param string $name
   *   The FOIA Request name.
   *
   * @return \Drupal\foia_request\Entity\FoiaRequestInterface
   *   The called FOIA Request entity.
   */
  public function setName($name);

  /**
   * Gets the FOIA Request creation timestamp.
   *
   * @return int
   *   Creation timestamp of the FOIA Request.
   */
  public function getCreatedTime();

  /**
   * Sets the FOIA Request creation timestamp.
   *
   * @param int $timestamp
   *   The FOIA Request creation timestamp.
   *
   * @return \Drupal\foia_request\Entity\FoiaRequestInterface
   *   The called FOIA Request entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the FOIA Request published status indicator.
   *
   * Unpublished FOIA Request are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the FOIA Request is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a FOIA Request.
   *
   * @param bool $published
   *   TRUE to set this FOIA Request to published,
   *   FALSE to set it to unpublished.
   *
   * @return \Drupal\foia_request\Entity\FoiaRequestInterface
   *   The called FOIA Request entity.
   */
  public function setPublished($published);

}
