<?php

/**
 * @file
 * Drush command to update FOIA request submissions.
 */

use Drupal\webform\Entity\WebformSubmission;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\foia_request\Entity\FoiaRequest;

/**
 * Implements hook_drush_command().
 */
function foia_request_drush_command() {
  $commands = [];

  $commands['queue-clean-request'] = [
    'description' => 'Queue clean FOIA request when files have been scanned.',
    'aliases' => ['qcr'],
  ];
  $commands['assume-emails-arrived'] = [
    'description' => 'Update in-transit email requests, marking them as submitted if they are old enough.',
    'aliases' => ['aea'],
  ];
  return $commands;
}

/**
 * Implements hook_drush_help().
 */
function foia_request_drush_help($section) {
  switch ($section) {
    case 'assume-emails-arrived':
      return dt('This command will update in-transit email requests, marking them as submitted if they are old enough.');

    case 'queue-clean-request':
      return dt('This command will update the status of a FOIA request if all the files have been scanned.');
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_foia_request_queue_clean_request() {
  $requests_to_be_scanned = foia_request_to_be_scanned();
  if (!empty($requests_to_be_scanned)) {
    foia_request_check_request($requests_to_be_scanned);
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_foia_request_assume_emails_arrived() {
  $deliveredEmails = foia_request_delivered_emails();
  foreach ($deliveredEmails as $deliveredEmail) {
    $foiaRequest = FoiaRequest::load($deliveredEmail->id);
    $foiaRequest->setRequestStatus(FoiaRequestInterface::STATUS_SUBMITTED);
    $foiaRequest->save();
  }
}

/**
 * Check each request to see if it can be queued.
 *
 * @param array $requests_to_be_scanned
 *   An array containing FOIA Requests IDs to be checked.
 */
function foia_request_check_request(array $requests_to_be_scanned) {
  foreach ($requests_to_be_scanned as $request) {
    $ready_to_queue = foia_request_check_file_fields($request);
    if ($ready_to_queue) {
      foia_request_queue_request($request);
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
function foia_request_check_file_fields($request) {
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
      $ready_to_queue = foia_request_check_file_statuses($data[$field_name]);
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
function foia_request_to_be_scanned() {
  $query = db_select('foia_request', 'fr');
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
function foia_request_delivered_emails() {
  $query = db_select('foia_request', 'fr');
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
function foia_request_check_file_statuses(array $file_ids) {
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
function foia_request_queue_request($request) {
  $foia_request = FoiaRequest::load($request->id);
  $enqueueing_status = \Drupal::service('foia_webform.foia_submission_queueing_service')
    ->enqueue($foia_request);

  if ($enqueueing_status) {
    foia_request_update_request_status($foia_request);
  }
}

/**
 * Update the Request status after enqueueing.
 *
 * @param \Drupal\foia_request\Entity\FoiaRequest $foia_request
 *   The FOIA Request entity.
 */
function foia_request_update_request_status(FoiaRequest $foia_request) {
  $foia_request->setRequestStatus(FoiaRequestInterface::STATUS_QUEUED);
  $foia_request->save();
}
