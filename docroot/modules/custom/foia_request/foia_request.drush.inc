<?php

/**
 * @file
 * Drush command to update FOIA request submissions.
 */

use Drupal\webform\entity\WebformSubmission;
use Drupal\file\Entity\File;
use Drupal\webform\WebformInterface;

/**
 * Implements hook_drush_command().
 */
function foia_request_drush_command() {
  $commands = [];

  $commands['update-foia-request'] = [
    'description' => 'Updates request status when files have been scanned.',
    'aliases' => ['ufr'],
  ];
  return $commands;
}

/**
 * Implements hook_drush_help().
 */
function foia_request_drush_help($section) {
  switch ($section) {
    case 'update-foia-request':
      return dt('This command will update the status of a FOIA request if all the files have been scanned.');
  }
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_foia_request_update_foia_request() {
  $requests_to_be_scanned = foia_request_to_be_scanned();
  if (!empty($requests_to_be_scanned)) {
    foreach ($requests_to_be_scanned as $request) {
      $ready_to_queue = foia_request_check_requests($request);
      if ($ready_to_queue) {
        foia_request_queue_request($request);
      }
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
function foia_request_check_requests($request) {
  $attachment_fields = &drupal_static(__FUNCTION__);
  $webform_submission = WebformSubmission::load($request->sid);
  $webform = $webform_submission->getWebform();
  $webform_id = $webform->id();
  $data = $webform_submission->getData();

  if (isset($attachment_fields[$webform_id])) {
    $file_fields = $attachment_fields[$webform_id];
  }
  else {
    $attachment_fields[$webform_id] = foia_request_get_file_fields($webform);
    $file_fields = $attachment_fields[$webform_id];
  }

  foreach ($file_fields as $field_name) {
    $ready_to_queue = foia_request_check_file_statuses($data[$field_name]);
    if (!$ready_to_queue) {
      return FALSE;
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
  $query->condition('fr.request_status', 3);
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
    $file = File::load($file_id);
    if ($file->hasField('field_virus_scan_status')) {
      $file_status = $file->get('field_virus_scan_status');
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
 * Returns all file attachment fields associated with a given Webform.
 *
 * @param \Drupal\webform\WebformInterface $webform
 *   The webform to check.
 *
 * @return array
 *   Returns an array containing the file field keyes.
 */
function foia_request_get_file_fields(WebformInterface $webform) {
  $elements = $webform->getElementsInitialized();
  $file_attachment_element_keys = [];
  foreach ($elements as $key => $element) {
    if (isset($element['#type']) && $element['#type'] == 'managed_file') {
      $file_attachment_element_keys[] = $key;
    }
  }
  return $file_attachment_element_keys;
}

/**
 * Adds the clean request to the Queue.
 *
 * @param object $request
 *   An database object containing the submission ID and foia_request ID.
 */
function foia_request_queue_request($request) {

}
