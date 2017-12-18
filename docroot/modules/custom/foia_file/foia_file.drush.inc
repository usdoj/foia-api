<?php

/**
 * @file
 * FOIA Api module drush commands.
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
      'fully_qualified_fine_name' => 'A filename that includes the complete absolute path on the server.',
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

  $fileDir = explode('/webform/', $scanResult, 2);
  $webformDir = "{$fileDir[0]}/webform/";

  // Get filename trimmed to webform dir
  // Temporarily delete all instances of the $webformDir.
  $trimmedFiles = str_replace($webformDir, '', $scanResult);

  // Sprint string into lines.
  // @todo change to preg split \R?
  $scans = explode("\\n", $trimmedFiles);

  foreach ($scans as $scan) {

    // Split the string into filename and status.
    preg_match('/[^ ]*$/', $scan, $wordsArray);
    $status = $wordsArray[0];

    // Get fully qualified file path (everything except for the last word)
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

  $endTime = microtime(TRUE);

  $executionTime = ($endTime - $startTime);

  $message .= "File Entity updater completed in {$executionTime} seconds.";

  return $message;

}
