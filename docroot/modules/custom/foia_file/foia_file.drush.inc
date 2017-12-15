<?php

/**
 * @file
 * FOIA Api module drush commands.
 */

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
      'scan_result' => 'The result of the file scan.'
    ],
    'aliases' => ['feu'],
  ];

  return $items;
}

// drush_foia_file_file_entity_updater_pre_validate


function drush_foia_file_file_entity_updater_validate($clamAvOutput) {

  //print_r($clamAvOutput);

  // regex for spaces and/or last word looking for OK, FOUND, or Removed.


/*  foreach (preg_split('/^\R$/', $clamAvOutput) as $line) {
    print_r($line);
    print_r("
    ");
  }*/

/*  $token = "newline";
  $line = strtok($clamAvOutput, $token);

  while ($line !== FALSE) {
    print_r($line);
    print_r("
    ");
    //$line = strtok($token);
    $line = strtok($token);
  }*/

}


/**
 * Implements drush_hook_COMMAND().
 *
 * @param string $file
 *  The fully qualified path name for a scanned file.
 *
 * @param string $scan_result
 *  The status for a scanned file.
 */
function drush_foia_file_file_entity_updater($clamAvOutput) {

  // Get filename trimmed to webform dir
  // Delete all instances of the string '/var/www/html/foia.uat/acquia-files/files-private/webform/'.

  $trimmedFiles = str_replace('/var/www/html/foia.uat/acquia-files/files-private/webform/', '', $clamAvOutput);


  // Sprint string into lines

  // This works VV @todo preg split \R???
  $scans = explode("\\n", $trimmedFiles);

//  $scans = preg_split("/$\\R?^/m", $clamAvOutput);
//  print_r($scans);

// This works VV
  foreach ($scans as $scan) {
    /*print_r($scan);
    print_r("\n");*/

    // Split the string into filename and status

    preg_match('/[^ ]*$/', $scan, $wordsArray);
    $status = $wordsArray[0];
    print_r($status);
    print_r(" | ");

    // Get fully qualified file path (everything except for the last word)

    //preg_replace('/$last');
    $scanLength = strlen($scan);
    $statusLength = strlen($status);
    $fileLength = $scanLength-$statusLength-2;
    $fileNameWithVirusName = substr($scan, 0, $fileLength);
    print_r($fileNameWithVirusName);
    print_r(" | ");

    // Trim virus description
    // regex = allowed file extension, colon, space, no allowed file extension

    preg_match('/(\.(pdf|txt|doc|docx|fodt|odt|gif|jpg|png))(: .*)?$/', $fileNameWithVirusName, $fileExtension);
    print_r('fileExtension');
    print_r($fileExtension);
    print_r($fileExtension[1]);
    $delimiter = $fileExtension[1];
    $fileName = substr($scan, 0, strpos($fileNameWithVirusName, $delimiter));
    print_r($fileName);

    print_r($fileExtension[1]);

    $fileNameWithExtension = $fileName.$fileExtension[1];
    print_r('!!!!!!!!!');
    print_r($fileNameWithExtension);

    // Determine file entity id based upon file path & name

    $query = \Drupal::entityQuery('file')
      ->condition('uri', "private://webform/{$fileNameWithExtension}");
    $fids = $query->execute();

    $fid = array_values($fids)[0];


    // Validate that only one item in fids array.

    // Nice to have >> verify that submission id is same as file path

    // Update file entity based upon status

    print_r($fid);
    print_r($status);

      // If clean or virus, set file entity status to clean or virus
    $fileEntity = Drupal\file_entity\Entity\FileEntity::load($fid);
    if ($status === "OK") {
      $fileEntity->set('field_virus_scan_status', 'clean');
    } elseif ($status === "Removed.") {
      $fileEntity->set('field_virus_scan_status', 'virus');
    }
    $fileEntity->save();


    // ??? handle white spaces from filename

    // ?????? determine what the final string is and use that as the status

    // file entity updater class
    print_r("\n");
  }

  /*switch ($scan_result) {
    case 'OK':

      $oks[] = $filePathName

  }

  set->('field_virus_scan_status')->*/
  $message = $clamAvOutput;

  //return $message;

}

// drush_foia_file_post_file_entity_updater