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
 * Implements drush_hook_COMMAND().
 */
function drush_foia_file_file_entity_update($scanOutput) {
  $startTime = microtime(TRUE);

  // Get webform directory on the server.
  $fileDir = explode('/webform/', $scanOutput, 2);
  $webformDir = "{$fileDir[0]}/webform/";

  // Temporarily delete similar paths of the webform dir during processing.
  $trimmedFiles = str_replace($webformDir, '', $scanOutput);

  // Split string into lines. @todo change to preg split \R?
  $scans = explode("\n", $trimmedFiles);

  foreach ($scans as $scan) {

    // Split the scan line into filename and status.
    preg_match('/[^ ]*$/', $scan, $wordsArray);
    // Set status to the last word of the line.
    $status = $wordsArray[0];

    $expectedStatuses = ['OK', 'Removed.', 'Empty file'];
    if (!in_array($status, $expectedStatuses)) {
      \Drupal::logger('foia_file')->warning("An unexpected status was detected in the virus scan output.");
    }

    // Get relative file path.
    $scanLength = strlen($scan);
    $statusLength = strlen($status);
    // Length of the file name is the remainder of the scan minus the colon and
    // space characters.
    $fileNameLength = $scanLength - $statusLength - 2;
    $fileName = substr($scan, 0, $fileNameLength);

    // Validate path, filename, and extension.
    $pathParts = pathinfo($fileName);
    foreach ($pathParts as $pathPart) {
      if (empty($pathPart)) {
        $status = 'invalid';
      }
    }

    // Log any discrepancies between filesystem and entities.
    if ($status === 'OK' && !is_file($fileName)) {
      \Drupal::logger('foia_file')->warning(
        "The file @fileName was not detected on the filesystem and was marked as clean by the virus scanner.",
        ['@fileName' => $fileName]
        );
    }
    if ($status === 'Removed.' && is_file($fileName)) {
      \Drupal::logger('foia_file')->warning(
        "The file @fileName was detected on the filesystem and a virus was detected in it. The file should have been deleted by the scanner.",
        ['@fileName' => $fileName]
      );
    }

    // Determine file entity id based upon file path & name.
    $query = \Drupal::entityQuery('file')
      ->condition('uri', "private://webform/{$fileName}");
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
    $doNotScanStatuses = ['clean', 'virus', 'invalid'];
    if ($fileEntity && !in_array($fileEntity->get('field_virus_scan_status')->getString(), $doNotScanStatuses)) {
      if ($status === "OK") {
        $fileEntity->set('field_virus_scan_status', 'clean');
        \Drupal::logger('foia_file')->info(
          "File @webformDir@fileName Entity ID @fid set to clean.",
          [
            '@webformDir' => $webformDir,
            '@fileName' => $fileName,
            '@fid' => $fid,
          ]
        );
      }
      elseif ($status === "Removed.") {
        $fileEntity->set('field_virus_scan_status', 'virus');
        \Drupal::logger('foia_file')->warning(
          "A virus was detected in the file @webformDir@fileName associated with the Entity ID @fid. The file has been deleted.",
          [
            '@webformDir' => $webformDir,
            '@fileName' => $fileName,
            '@fid' => $fid,
          ]
        );
        \Drupal::logger('foia_file')->notice(
          "File @webformDir@fileName Entity ID @fid set to virus.",
          [
            '@webformDir' => $webformDir,
            '@fileName' => $fileName,
            '@fid' => $fid,
          ]
        );
      }
      $fileEntity->save();
    }
    else {
      if ($fileEntity) {
        \Drupal::logger('foia_file')->info(
          "File @webformDir@fileName Entity ID @fid has already been scanned.",
          [
            '@webformDir' => $webformDir,
            '@fileName' => $fileName,
            '@fid' => $fid,
          ]
        );
      }
      else {
        \Drupal::logger('foia_file')->warning(
          "There is no entity associated with @fileName.",
          ['@fileName' => $fileName]
        );
      }
    }

  }

  $endTime = microtime(TRUE);

  $executionTime = ($endTime - $startTime);

  \Drupal::logger('foia_file')->info("File Entity update completed in {$executionTime} seconds.");

}
