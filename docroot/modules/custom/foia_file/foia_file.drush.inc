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

/**
 * Implements drush_hook_COMMAND().
 *
 * @param string $file
 *  The fully qualified path name for a scanned file.
 *
 * @param string $scan_result
 *  The status for a scanned file.
 */
function drush_foia_file_entity_updater(string $filePathName, string $scan_result) {

  switch ($scan_result) {
    case
  }

  set->('field_virus_scan_status')->
}
