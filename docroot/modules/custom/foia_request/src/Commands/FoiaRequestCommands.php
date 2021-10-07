<?php

namespace Drupal\foia_request\Commands;

use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\foia_request\Entity\FoiaRequest;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class FoiaRequestCommands extends DrushCommands {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * FoiaUploadXmlCommands constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    parent::__construct();
    $this->connection = $database;
  }

  /**
   * Queue clean FOIA request when files have been scanned.
   *
   * @command queue:clean-request
   * @aliases qcr,queue-clean-request
   */
  public function cleanRequest() {
    $requests_to_be_scanned = $this->toBeScanned();
    if (!empty($requests_to_be_scanned)) {
      $this->checkRequest($requests_to_be_scanned);
    }
  }

  /**
   * Update in-transit email requests, marking them as submitted if they're old.
   *
   * @command assume:emails-arrived
   * @aliases aea,assume-emails-arrived
   */
  public function emailsArrived() {
    $deliveredEmails = $this->deliveredEmails();
    foreach ($deliveredEmails as $deliveredEmail) {
      $foiaRequest = FoiaRequest::load($deliveredEmail->id);
      $foiaRequest->setRequestStatus(FoiaRequestInterface::STATUS_SUBMITTED);
      $foiaRequest->save();
      // We also need to now delete the webform submission.
      $webformSubmissionId = $foiaRequest->get('field_webform_submission_id')->value;
      $webformSubmission = WebformSubmission::load($webformSubmissionId);
      $webformSubmission->delete();
    }
  }

  /**
   * Check each request to see if it can be queued.
   *
   * @param array $requests_to_be_scanned
   *   An array containing FOIA Requests IDs to be checked.
   */
  public function checkRequest(array $requests_to_be_scanned) {
    foreach ($requests_to_be_scanned as $request) {
      $ready_to_queue = $this->checkFileFields($request);
      if ($ready_to_queue) {
        $this->queueRequest($request);
      }
    }
  }

  /**
   * Checks files for every file field on a given request.
   *
   * @param object $request
   *   An database object containing the submission ID and foia_request ID.
   *
   * @return bool
   *   Returns TRUE if all of the files have been scanned, else FALSE.
   */
  public function checkFileFields($request) {
    $attachment_fields = &drupal_static(__FUNCTION__);
    $webform_submission = WebformSubmission::load($request->sid);

    if (isset($attachment_fields[$request->webform_id])) {
      $file_fields = $attachment_fields[$request->webform_id];
    }
    else {
      $webform = $webform_submission->getWebform();
      $file_field_lookup = \Drupal::service('foia_webform.file_field_lookup_service');
      $attachment_fields[$request->webform_id] = $file_field_lookup->getFileAttachmentElementsOnWebform($webform);
      $file_fields = $attachment_fields[$request->webform_id];
    }

    if (isset($file_fields)) {
      $data = $webform_submission->getData();
      foreach ($file_fields as $field_name) {
        $ready_to_queue = $this->checkFileStatuses($data[$field_name]);
        if (!$ready_to_queue) {
          return FALSE;
        }
      }
    }
    return $ready_to_queue;
  }

  /**
   * Query for FOIA requests whose status is set to "To Be Scanned".
   */
  public function toBeScanned() {
    $query = $this->connection->select('foia_request', 'fr');
    $query->addField('fr', 'id');
    $query->join('foia_request__field_webform_submission_id', 'frid', 'frid.entity_id = fr.id');
    $query->addField('frid', 'field_webform_submission_id_value', 'sid');
    $query->join('webform_submission', 'ws', 'ws.sid = frid.field_webform_submission_id_value');
    $query->addField('ws', 'webform_id');
    $query->condition('fr.request_status', FoiaRequestInterface::STATUS_SCAN);
    return $query->execute()->fetchAll();
  }

  /**
   * Query for FOIA email requests that are now assumed to have arrived.
   *
   * The criteria for this assumption are that they are old enough, based on the
   * FoiaRequestInterface::ASSUME_DELIVERED_AFTER constant.
   */
  public function deliveredEmails() {
    $query = $this->connection->select('foia_request', 'fr');
    $query->addField('fr', 'id');
    $query->condition('fr.request_status', FoiaRequestInterface::STATUS_IN_TRANSIT);
    $query->condition('fr.created', time() - FoiaRequestInterface::ASSUME_DELIVERED_AFTER, '<');
    return $query->execute()->fetchAll();
  }

  /**
   * Check the status of files to see if they've been scanned.
   *
   * @param array $file_ids
   *   An array containing file IDs.
   */
  public function checkFileStatuses(array $file_ids) {
    $ready_to_queue = FALSE;
    foreach ($file_ids as $file_id) {
      $file = FileEntity::load($file_id);
      if ($file->hasField('field_virus_scan_status')) {
        $file_status = $file->get('field_virus_scan_status')->value;
        if ($file_status === 'scan') {
          return FALSE;
        }
        else {
          $ready_to_queue = TRUE;
        }
      }
    }
    return $ready_to_queue;
  }

  /**
   * Adds the clean request to the Queue.
   *
   * @param object $request
   *   An database object containing the submission ID and foia_request ID.
   */
  public function queueRequest($request) {
    $foia_request = FoiaRequest::load($request->id);
    $enqueueing_status = \Drupal::service('foia_webform.foia_submission_queueing_service')
      ->enqueue($foia_request);

    if ($enqueueing_status) {
      $this->updateRequestStatus($foia_request);
    }
  }

  /**
   * Update the Request status after enqueueing.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequest $foia_request
   *   The FOIA Request entity.
   */
  public function updateRequestStatus(FoiaRequest $foia_request) {
    $foia_request->setRequestStatus(FoiaRequestInterface::STATUS_QUEUED);
    $foia_request->save();
  }

}
