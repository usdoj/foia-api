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
$personnel_data = extract_personnel_from_components($component_data['components']);

write_data_to_json_file("{$modified_data_files_directory}/agencies.json", $agency_data);
write_data_to_json_file("{$modified_data_files_directory}/components.json", $component_data);
write_data_to_json_file("{$modified_data_files_directory}/personnel.json", $personnel_data);

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
 * Extract FOIA personnel from components and return array of personnel.
 *
 * @param array $components
 *   Array of all components.
 *
 * @return array
 *   Array of all FOIA personnel.
 */
function extract_personnel_from_components(array &$components) {
  $id = 1;
  $foia_personnel = [];
  foreach ($components as &$component) {
    if (isset($component->foia_officer)) {
      $foia_officer = set_personnel_id($foia_personnel, $component, 'foia_officer', $id);
      if ($foia_officer) {
        assign_agency_name_to_personnel($component, $foia_officer);
        $foia_personnel['personnel'][] = $foia_officer;
      }
    }
    if (isset($component->public_liaison)) {
      $public_liaison = set_personnel_id($foia_personnel, $component, 'public_liaison', $id);
      if ($public_liaison) {
        assign_agency_name_to_personnel($component, $public_liaison);
        $foia_personnel['personnel'][] = $public_liaison;
      }
    }
    if (isset($component->service_center)) {
      $service_center = set_personnel_id($foia_personnel, $component, 'service_center', $id);
      if ($service_center) {
        assign_agency_name_to_personnel($component, $service_center);
        $foia_personnel['personnel'][] = $service_center;
      }
    }
  }

  return $foia_personnel;
}

/**
 * Assigns a unique numerical ID to agency components.
 *
 * @param object $component
 *   Agency component object.
 * @param int $id
 *   Unique numerical ID assigned to an agency component.
 */
function set_component_id(&$component, &$id) {
  $component->id = $id;
  $id++;
}

/**
 * Assign each FOIA personnel an ID to aid in the migration effort.
 *
 * @param object $component
 *   Agency component object.
 * @param string $personnel_type
 *   Type of personnel (e.g. foia_officer)
 * @param int $id
 *   Numerical ID to assign to FOIA personnel.
 *
 * @return mixed
 *   FOIA Personnel object with an assigned numerical ID if dealing with new
 *   personnel, FALSE otherwise.
 */
function set_personnel_id(&$personnel, &$component, $personnel_type, &$id) {
  $individual_personnel = $component->{$personnel_type};
  unset($component->{$personnel_type});
  $component->{$personnel_type} = new stdClass();
  $existing_id = '';
  if (isset($personnel['personnel'])) {
    $existing_id = get_existing_personnel_id($personnel['personnel'], $component, $individual_personnel);
  }
  if ($existing_id) {
    $component->{$personnel_type}->id = $existing_id;
    return FALSE;
  }
  else {
    $component->{$personnel_type}->id = $id;
    $individual_personnel->id = $id;
    $id++;
    return $individual_personnel;
  }
}

/**
 * Checks for an existing personnel before creating a new personnel object.
 *
 * @param array $existing_personnel
 *   All existing personnel objects created.
 * @param object $component
 *   Agency component object.
 * @param object $individual_personnel
 *   Individual personnel to check existing personnel for.
 *
 * @return mixed
 *   Numerical ID of existing matching personnel, FALSE otherwise.
 */
function get_existing_personnel_id(array $existing_personnel, $component, $individual_personnel) {
  $name_to_check = isset($individual_personnel->name) ? $individual_personnel->name : '';
  $phone_numbers_to_check = isset($individual_personnel->phone) ? $individual_personnel->phone : [];
  $agency_to_check = isset($component->agency_name) ? $component->agency_name : '';
  foreach ($existing_personnel as $existing_individual_personnel) {
    $existing_name = isset($existing_individual_personnel->name) ? $existing_individual_personnel->name : '';
    $existing_phone_numbers = isset($existing_individual_personnel->phone) ? $existing_individual_personnel->phone : [];
    $existing_agency_name = isset($existing_individual_personnel->agency_name) ? $existing_individual_personnel->agency_name : '';
    if (
      $name_to_check == $existing_name
      && $phone_numbers_to_check == $existing_phone_numbers
      && $agency_to_check == $existing_agency_name
    ) {
      return $existing_individual_personnel->id;
    }
  }
  return FALSE;
}

/**
 * Assigns an agency name to a FOIA personnel object.
 *
 * @param object $component
 *   The agency component the FOIA personnel belongs to.
 * @param object $foia_personnel
 *   The FOIA personnel object.
 */
function assign_agency_name_to_personnel($component, &$foia_personnel) {
  if (isset($component->agency_name)) {
    $foia_personnel->agency_name = $component->agency_name;
  }
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
  $id = 1;
  foreach ($agencies as $agency) {
    $name = $agency->name;
    $components = $agency->departments;
    foreach ($components as $component) {
      $component->agency_name = $name;
      set_component_id($component, $id);
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
