<?php

/**
 * @file
 * FOIA Api module drush command.
 */

use Drupal\file_entity\Entity\FileEntity;

/**
 * Implements hook_drush_command().
 */
function foia_file_drush_command() {
  $items = [];

  $items['file-entity-updater'] = [
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
 * Implements drush_hook_COMMAND().
 */
function drush_foia_file_file_entity_updater($scanResult) {

  $startTime = microtime(TRUE);

  $message = "File Entity updater started. *******************************\n";

  // Get webform directory on the server.
  $fileDir = explode('/webform/', $scanResult, 2);
  $webformDir = "{$fileDir[0]}/webform/";

  // Temporarily delete instances of the webform directory during processing.
  $trimmedFiles = str_replace($webformDir, '', $scanResult);

  // Split string into lines. @todo change to preg split \R?
  $scans = explode("\n", $trimmedFiles);

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
    preg_match('/(\.(pdf|txt|doc|docx|fodt|odt|gif|jpg|png))(: .*)?$/', $fileNameWithVirusName, $fileExtension);
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
    $fileEntity = FileEntity::load($fid);
    if ($fileEntity && $fileEntity->get('field_virus_scan_status') === 'scan') {
      if ($status === "OK") {
        $fileEntity->set('field_virus_scan_status', 'clean');
        $message .= "File {$webformDir}{$fileNameWithExtension} Entity ID {$fid} set to clean.\n";
      }
      elseif ($status === "Removed.") {
        $fileEntity->set('field_virus_scan_status', 'virus');
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

  $message .= "File Entity updater completed in {$executionTime} seconds.";

  return $message;

}
