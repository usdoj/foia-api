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

  // Set constants.
  $cleanScanStatuses = ['OK', 'Empty file'];
  $doNotScanEntityStatuses = ['clean', 'virus'];

  foreach ($scans as $scan) {

    // Validate the scan output status.
    preg_match('/ (OK|Empty file|FOUND|Removed\.)$/', $scan, $scanStatusWithLeadingSpace);
    // Do not process scan status "FOUND".
    // These will be processed under the duplicate "Remove." scan status.
    if ($scanStatusWithLeadingSpace[0] === ' FOUND') {
      continue;
    }
    if ($scanStatusWithLeadingSpace) {
      // Trim the leading space.
      $scanStatus = substr($scanStatusWithLeadingSpace[0], 1);
    }
    else {
      \Drupal::logger('foia_file')->warning(
        "An unexpected scan status was detected in the virus scan output."
      );
      continue;
    }

    $relativeFileName = preg_replace('/: (OK|Empty file|Removed\.)$/', '', $scan);
    $absoluteFileName = $webformDir . $relativeFileName;
    $pathParts = pathinfo($absoluteFileName);

    // Log any discrepancies between filesystem and entities.
    if (in_array($scanStatus, $cleanScanStatuses) && !file_exists($absoluteFileName)) {
      \Drupal::logger('foia_file')->warning(
        "The file @absoluteFileName was not detected on the filesystem but was marked as clean by the virus scanner.",
        ['@absoluteFileName' => $absoluteFileName]
      );
    }
    if ($scanStatus === 'Removed.' && file_exists($absoluteFileName)) {
      \Drupal::logger('foia_file')->warning(
        "The file @absoluteFileName was detected on the filesystem and a virus was detected in it. The file should have been deleted by the scanner.",
        ['@absoluteFileName' => $absoluteFileName]
      );
    }

    // Determine file entity id based upon file path & name.
    $query = \Drupal::entityQuery('file')
      ->condition('uri', "private://webform/{$relativeFileName}");
    $fids = $query->execute();
    if ($fids) {
      $fid = array_values($fids)[0];
    }

    // @todo Validate that only one item in fids array.

    // @todo Nice to have >> verify that submission id is same as file path.

    // Update file entity based upon scanStatus.
    if ($fid) {
      $file = File::load($fid);
    }
    else {
      $file = NULL;
    }
    if ($file && !in_array($file->get('field_virus_scan_status')->getString(), $doNotScanEntityStatuses)) {
      if (in_array($scanStatus, $cleanScanStatuses)) {
        if ($file->hasField('field_virus_scan_status')) {
          $file->set('field_virus_scan_status', 'clean');
        }
        else {
          \Drupal::logger('foia_file')->warning(
            "The file @absoluteFileName with the ID @fid does not have a \"Virus scan scanStatus\" field and was not able to be marked as clean even though it passed the file scan.",
            [
              '@absoluteFileName' => $absoluteFileName,
              '@fid' => $fid,
            ]);
        }
      }
      elseif ($scanStatus === "Removed.") {
        $file->set('field_virus_scan_status', 'virus');
        \Drupal::logger('foia_file')->warning(
          "A virus was detected in the file @absoluteFileName. The file has been deleted. The associated Entity ID @fid has been set to virus.",
          [
            '@absoluteFileName' => $absoluteFileName,
            '@fid' => $fid,
          ]);
      }
      $file->save();
    }

    if (array_search('', $pathParts) === TRUE) {
      \Drupal::logger('foia_file')->warning("An invalid path of @path, filename of @relativeFileName, or extension of @extension was detected.",
      [
        '@path' => $pathParts['dirname'],
        '@relativeFileName' => $pathParts['filename'],
        '@extension' => $pathParts['extension'],
      ]
      );
    }

  }

  $endTime = microtime(TRUE);

  $executionTime = ($endTime - $startTime);

  \Drupal::logger('foia_file')->info("File Entity update completed in {$executionTime} seconds.");

}
