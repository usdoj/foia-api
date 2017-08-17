<?php

/**
 * @file
 * Script to prep JSON source data for migration use.
 */

$original_data_files_directory = '../data/original';
$modified_data_files_directory = '../data/modified';

// Always start the script by clearing out the modified data files directory.
delete_files_from_directory($modified_data_files_directory);

// Make necessary changes to JSON to prep it for migration use.
modify_json($original_data_files_directory, $modified_data_files_directory);

/**
 * Delete all files in a specified directory.
 *
 * @param string $directory_path
 *   The path to the directory.
 */
function delete_files_from_directory($directory_path) {
  if (file_exists($directory_path)) {
    $files = glob("{$directory_path}/*");
    foreach ($files as $file) {
      if (is_file($file)) {
        unlink($file);
      }
    }
  }
}

/**
 * Modify JSON files to prep for use in migration.
 *
 * @param string $path_to_original_files
 *   Path to the directory that houses the original JSON files.
 * @param string $path_to_new_files
 *   Path to the directory that houses the modified JSON files.
 */
function modify_json($path_to_original_files, $path_to_new_files) {
  $json_files = glob("{$path_to_original_files}/*");
  foreach ($json_files as $json_file) {
    $original_data = json_decode(file_get_contents($json_file));
    $modified_data = wrap_in_array($original_data);
    $json_file_name = basename($json_file);
    $modified_data_file_name = "{$path_to_new_files}/{$json_file_name}";
    write_data_to_json_file($modified_data_file_name, $modified_data);
  }
}

/**
 * Takes passed in data and returns it in an array.
 *
 * @param mixed $data
 *   Piece of data to wrap in an array.
 *
 * @return array
 *   Data wrapped in an array
 */
function wrap_in_array($data) {
  return [$data];
}

/**
 * Converts passed in data to JSON and writes it to a specified file.
 *
 * @param string $file_name
 *   File to write JSON to.
 * @param array $data
 *   Data to convert to JSON and write to disk.
 */
function write_data_to_json_file($file_name, array $data) {
  file_put_contents($file_name, json_encode($data, JSON_PRETTY_PRINT));
}
