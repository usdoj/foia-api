<?php

/**
 * @file
 * FOIA Api module drush command.
 */

use Drupal\file\Entity\File;

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

    $cleanStatuses = ['OK', 'Empty file'];

    // Log any discrepancies between filesystem and entities.
    if (in_array($status, $cleanStatuses) && !is_file($fileName)) {
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
    if ($fids) {
      $fid = array_values($fids)[0];
    }

    // @todo Validate that only one item in fids array.

    // @todo Nice to have >> verify that submission id is same as file path.

    // Update file entity based upon status.
    if ($fid) {
      $file = File::load($fid);
    }
    else {
      $file = NULL;
    }
    $doNotScanStatuses = ['clean', 'virus'];
    if ($file && !in_array($file->get('field_virus_scan_status')->getString(), $doNotScanStatuses)) {
      if (in_array($status, $cleanStatuses)) {
        if ($file->hasField('field_virus_scan_status')) {
          $file->set('field_virus_scan_status', 'clean');
        }
        else {
          \Drupal::logger('foia_file')->warning(
            "The file @webformDir@fileName with the ID @fid does not have a \"Virus scan status\" field and was not able to be marked as clean even though it passed the file scan.",
            [
              '@weformDir' => $webformDir,
              '@fileName' => $fileName,
              '@fid' => $fid,
            ]);
        }
      }
      elseif ($status === "Removed.") {
        $file->set('field_virus_scan_status', 'virus');
        \Drupal::logger('foia_file')->warning(
          "A virus was detected in the file @webformDir@fileName. The file has been deleted. The associated Entity ID @fid has been set to virus.",
          [
            '@webformDir' => $webformDir,
            '@fileName' => $fileName,
            '@fid' => $fid,
          ]);
      }
      $file->save();
    }

    else {
      \Drupal::logger('foia_file')->warning("An invalid path of @path, filename of @fileName, or extension of @extension was detected.",
      [
        '@path' => $pathParts['dirname'],
        '@fileName' => $pathParts['filename'],
        '@extension' => $pathParts['extension'],
      ]
      );
    }

  }

  $endTime = microtime(TRUE);

  $executionTime = ($endTime - $startTime);

  \Drupal::logger('foia_file')->info("File Entity update completed in {$executionTime} seconds.");

}
