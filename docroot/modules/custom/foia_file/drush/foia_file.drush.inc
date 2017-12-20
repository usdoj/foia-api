<?php

/**
 * @file
 * FOIA Api module drush command.
 */

use Drupal\file_entity\Entity\FileEntity;
use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function foia_file_drush_command() {
  $items = [];

  $items['file-entity-update'] = [
    'description' => 'Updates file entities virus scan statuses.',
    'core' => ['8+'],
    'arguments' => [
      'scan_result' => 'The result of the file scan.',
    ],
    'aliases' => ['feu'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function foia_file_drush_help($command) {
  switch ($command) {
    case 'drush:file-entity-update':
      return dt('Update virus scan statuses for file entites based upon output from virus scanner.');
  }
}

/**
 * Implements drush_hook_COMMAND_validate().
 */
function drush_foia_file_file_entity_update_validate($scanOutput) {

  $startTime = microtime(TRUE);

  drush_log("File Entity update validation started. ***********************", LogLevel::INFO);

  // Get allowed extensions.
  $config = \Drupal::config('file_entity.settings');
  $extensions = $config->get('default_allowed_extensions');
  $pipeDelimitedExtensions = str_replace(" ", "|", $extensions);
  $scans = explode("\n", $scanOutput);

  foreach ($scans as $scan) {
    if (!isset($scan) || is_null($scan)) {
      $msg = 'A blank line was output by the virus scanner.';
      $msg .= 'This is not expected behavior.';
      drush_log($msg, LogLevel::WARNING);
    }

    // Split the scan line into filename and status.
    preg_match('/[^ ]*$/', $scan, $wordsArray);
    // Set status to the last word of the line.
    $status = $wordsArray[0];

    $validStates = ['OK', 'Removed.', 'FOUND'];
    if (!in_array($status, $validStates)) {
      $msg = 'An invalid status was detected in the virus scan output.';
      $msg .= 'Virus scanning will not continue until this is resolved!!!';
      return drush_set_error('POLICY_PM_DENY', dt($msg));
    }

    // Get relative file path.
    $scanLength = strlen($scan);
    $statusLength = strlen($status);
    $fileLength = $scanLength - $statusLength - 2;
    $fileNameWithVirusName = substr($scan, 0, $fileLength);

    // Trim virus description.
    preg_match('/(\.(' . $pipeDelimitedExtensions . '))(: .*)?$/', $fileNameWithVirusName, $fileExtension);
    $delimiter = $fileExtension[1];
    $fileName = substr($scan, 0, strpos($fileNameWithVirusName, $delimiter));
    $fileNameWithExtension = $fileName . $fileExtension[1];

    if ($status === 'OK' && !is_file($fileNameWithExtension)) {
      $msg = "The file {$fileNameWithExtension} was not detected on the filesystem and was marked as clean by the virus scanner.";
      drush_log($msg, LogLevel::WARNING);
    }

    if ($status === 'FOUND' || $status === 'Removed.') {
      $msg = "The file {$fileNameWithExtension} was detected on the filesystem and was marked as infected by a virus. It should have been deleted by the scanner.";
      drush_log($msg, LogLevel::WARNING);
    }
  }

  $endTime = microtime(TRUE);

  $executionTime = ($endTime - $startTime);

  drush_log("File Entity update validation completed in {$executionTime} seconds.", LogLevel::INFO);

}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_foia_file_file_entity_update($scanOutput) {
  $startTime = microtime(TRUE);

  $message = "File Entity update started. **********************************\n";

  // Get webform directory on the server.
  $fileDir = explode('/webform/', $scanOutput, 2);
  $webformDir = "{$fileDir[0]}/webform/";

  // Temporarily delete similar paths of the webform dir during processing.
  $trimmedFiles = str_replace($webformDir, '', $scanOutput);

  // Split string into lines. @todo change to preg split \R?
  $scans = explode("\n", $trimmedFiles);

  // Get allowed extensions.
  $config = \Drupal::config('file_entity.settings');
  $extensions = $config->get('default_allowed_extensions');
  $pipeDelimitedExtensions = str_replace(" ", "|", $extensions);

  foreach ($scans as $scan) {

    // Split the scan line into filename and status.
    preg_match('/[^ ]*$/', $scan, $wordsArray);
    // Set status to the last word of the line.
    $status = $wordsArray[0];

    // Get relative file path.
    $scanLength = strlen($scan);
    $statusLength = strlen($status);
    $fileLength = $scanLength - $statusLength - 2;
    $fileNameWithVirusName = substr($scan, 0, $fileLength);

    // Trim virus description.
    preg_match('/(\.(' . $pipeDelimitedExtensions . '))(: .*)?$/', $fileNameWithVirusName, $fileExtension);
    $delimiter = $fileExtension[1];
    $fileName = substr($scan, 0, strpos($fileNameWithVirusName, $delimiter));
    $fileNameWithExtension = $fileName . $fileExtension[1];

    // Determine file entity id based upon file path & name.
    $query = \Drupal::entityQuery('file')
      ->condition('uri', "private://webform/{$fileNameWithExtension}");
    $fids = $query->execute();
    $fid = array_values($fids)[0];

    // @todo Validate that only one item in fids array.

    // @todo Nice to have >> verify that submission id is same as file path.

    // Update file entity based upon status.
    if ($fid) {
      $fileEntity = FileEntity::load($fid);
    }
    else {
      $fileEntity = NULL;
    }
    $doNotScanStates = ['clean', 'virus'];
    if ($fileEntity && !in_array($fileEntity->get('field_virus_scan_status')->getString(), $doNotScanStates)) {
      if ($status === "OK") {
        $fileEntity->set('field_virus_scan_status', 'clean');
        $message .= "File {$webformDir}{$fileNameWithExtension} Entity ID {$fid} set to clean.\n";
      }
      elseif ($status === "Removed.") {
        $fileEntity->set('field_virus_scan_status', 'virus');
        Drupal::logger('foia_file')->warning(
          "A virus was detected in the file @webformDir@fileNameWithExtension associated with the Entity ID @fid. The file has been deleted.",
          [
            '@webformDir' => $webformDir,
            '@fileNameWithExtension' => $fileNameWithExtension,
            '@fid' => $fid,
          ]
        );
        $message .= "File {$webformDir}{$fileNameWithExtension} Entity ID {$fid} set to virus.\n";
      }
      $fileEntity->save();
    }
    else {
      if ($fileEntity) {
        $message .= "File {$webformDir}{$fileNameWithExtension} Entity ID {$fid} has already been scanned {$fileNameWithExtension}.\n";
      }
      else {
        $message .= "There is no entity associated with {$fileNameWithExtension}.\n";
      }
    }

  }

  $endTime = microtime(TRUE);

  $executionTime = ($endTime - $startTime);

  $message .= "File Entity update completed in {$executionTime} seconds.";

  return $message;

}
