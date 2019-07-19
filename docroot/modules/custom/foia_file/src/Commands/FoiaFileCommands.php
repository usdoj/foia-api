<?php

namespace Drupal\foia_file\Commands;

use Drush\Commands\DrushCommands;
use Drupal\file\Entity\File;

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
class FoiaFileCommands extends DrushCommands {

  /**
   * Performs virus scan and updates status on file entities accordingly.
   *
   * @param string $path_to_executable
   *   The absolute path of the virus scanning executable.
   * @param string $path_to_webform_attachments
   *   The absolute path of the webform attachments to scan.
   *
   * @command virus:scan
   * @aliases vs,virus-scan
   */
  public function scan($path_to_executable, $path_to_webform_attachments) {
    if (empty($path_to_executable) || empty($path_to_webform_attachments)) {
      \Drupal::logger('foia_file')->error('Virus scanning executable path or directory to scan missing.');
      return FALSE;
    }

    $scan_options = '-r --remove --no-summary';
    $scan_command = "${path_to_executable} ${scan_options} ${path_to_webform_attachments}";
    $startTime = microtime(TRUE);
    $scanOutput = shell_exec($scan_command);

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
        $scanStatus = ltrim($scanStatusWithLeadingSpace[0], " ");
      }
      else {
        \Drupal::logger('foia_file')->warning(
          "An unexpected output was detected in the virus scan: @scan",
          ['@scan' => $scan]
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
            \Drupal::logger('foia_file')->warning(
              "The file @absoluteFileName with the ID @fid was scanned and updated.",
              [
                '@absoluteFileName' => $absoluteFileName,
                '@fid' => $fid,
              ]
            );
          }
          else {
            \Drupal::logger('foia_file')->warning(
              "The file @absoluteFileName with the ID @fid does not have a \"Virus scan scanStatus\" field and was not able to be marked as clean even though it passed the file scan.",
              [
                '@absoluteFileName' => $absoluteFileName,
                '@fid' => $fid,
              ]
            );
          }
        }
        elseif ($scanStatus === "Removed.") {
          $file->set('field_virus_scan_status', 'virus');
          \Drupal::logger('foia_file')->warning(
            "A virus was detected in the file @absoluteFileName. The file has been deleted. The associated Entity ID @fid has been set to virus.",
            [
              '@absoluteFileName' => $absoluteFileName,
              '@fid' => $fid,
            ]
          );
        }
        $file->save();
      }

      if (array_search('', $pathParts) === TRUE) {
        \Drupal::logger('foia_file')->warning(
          "An invalid path of @path, filename of @relativeFileName, or extension of @extension was detected.",
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

    \Drupal::logger('foia_file')->info("Virus scanning and file updates completed in {$executionTime} seconds.");

  }

}
