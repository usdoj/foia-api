<?php

/**
 * @file
 * Script to prep JSON source data for migration use.
 */

$original_data_files_directory = '../data/original';
$modified_data_files_directory = '../data/modified';

// Always start the script by clearing out the modified data files directory.
delete_files_from_directory($modified_data_files_directory);

$agency_data = extract_agencies_from_source_files($original_data_files_directory);
$component_data = extract_components_from_agencies($agency_data['agencies']);
remove_departments_property_from_agencies($agency_data);

write_data_to_json_file("{$modified_data_files_directory}/agencies.json", $agency_data);
write_data_to_json_file("{$modified_data_files_directory}/components.json", $component_data);

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
 * Extract agencies from separate json files, return single array of agencies.
 *
 * @param string $path_to_original_files
 *   Path to the directory that houses the original JSON files.
 *
 * @return mixed
 *   Agency data
 */
function extract_agencies_from_source_files($path_to_original_files) {
  $json_files = glob("{$path_to_original_files}/*");
  foreach ($json_files as $json_file) {
    $agency = json_decode(file_get_contents($json_file));
    $agencies['agencies'][] = $agency;
  }
  return $agencies;
}

/**
 * Extract components from agencies and return single array of components.
 *
 * @param array $agencies
 *   Array of all agencies.
 *
 * @return array
 *   Array of all components.
 */
function extract_components_from_agencies(array $agencies) {
  $components['components'] = get_components_with_agency_name($agencies);
  return $components;
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

/**
 * Add agency_name to each component and return them.
 *
 * @param array $agencies
 *   Array of all agencies.
 *
 * @return array
 *   Array of all components.
 */
function get_components_with_agency_name(array $agencies) {
  foreach ($agencies as $agency) {
    $name = $agency->name;
    $components = $agency->departments;
    foreach ($components as $component) {
      $component->agency_name = $name;
      $components_with_agency_name[] = $component;
    }
  }

  return $components_with_agency_name;
}

/**
 * Remove departments property from agencies.
 *
 * @param array $agency_data
 *   Agency data.
 */
function remove_departments_property_from_agencies(array &$agency_data) {
  $agencies = $agency_data['agencies'];
  foreach ($agencies as $agency) {
    unset($agency->departments);
  }
}
