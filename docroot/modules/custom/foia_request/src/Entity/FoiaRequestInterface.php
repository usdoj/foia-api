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
  const STATUS_QUEUED = 0;

  /**
   * Request has been submitted.
   */
  const STATUS_SUBMITTED = 1;

  /**
   * Submission has failed.
   */
  const STATUS_FAILED = 2;

  /**
   * Sent to component via email.
   */
  const METHOD_EMAIL = 'email';

  /**
   * Sent to component via api.
   */
  const METHOD_API = 'api';

  /**
   * Gets the status of the foia_request entity.
   *
   * @return int
   *   One of FoiaRequestInterface::QUEUED or
   *   FoiaRequestInterface::SUBMITTED or
   *   FoiaRequestInterface::FAILED.
   */
  public function getRequestStatus();

  /**
   * Sets the status of the foia_request entity.
   *
   * @param int $requestStatus
   *   Set to QUEUED to mark enqueued,
   *   SUBMITTED to mark submitted,
   *   FAILED to mark failure.
   *
   * @return \Drupal\foia_request\Entity\FoiaRequestInterface
   *   The called foia_request entity.
   */
  public function setRequestStatus($requestStatus);

  /**
   * Returns an array of valid statuses for a FOIA request.
   *
   * @return array
   *   Valid statuses for a FOIA request.
   */
  public static function getValidRequestStatuses();

  /**
   * Gets the method by which the request was submitted to the component.
   *
   * @return string
   *   One of FoiaRequestInterface::METHOD_EMAIL or
   *   FoiaRequestInterface::METHOD_API.
   */
  public function getSubmissionMethod();

  /**
   * Sets the method by which the request was submitted to the component.
   *
   * @param string $submissionMethod
   *   The submission method.
   */
  public function setSubmissionMethod($submissionMethod);

  /**
   * Returns array of valid methods by which request can be sent to component.
   *
   * @return array
   *   Valid submission methods for a FOIA request.
   */
  public static function getValidSubmissionMethods();

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

}
