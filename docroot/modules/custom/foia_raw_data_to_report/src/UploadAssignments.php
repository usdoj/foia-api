<?php

namespace Drupal\foia_raw_data_to_report;

use Drupal\node\NodeInterface;

/**
 * Checks component assignments independently of CSV contents.
 */
final class UploadAssignments {

  /**
   * Returns assignment errors indexed by paragraph delta.
   */
  public static function validate(NodeInterface $node): array {
    $errors = [];
    $seen = [];
    $agency = $node->get('field_agency')->target_id;
    foreach ($node->get('field_component_uploads') as $delta => $item) {
      $upload = $item->entity;
      if (!$upload || $upload->bundle() !== 'raw_data_component_upload') {
        $errors[$delta][] = 'The component upload is missing or has an unsupported type.';
        continue;
      }
      $component = $upload->get('field_agency_component')->entity;
      if (!$component instanceof NodeInterface || $component->bundle() !== 'agency_component') {
        $errors[$delta][] = 'Select an Agency Component.';
      }
      else {
        if (!$agency || (string) $component->get('field_agency')->target_id !== (string) $agency) {
          $errors[$delta][] = 'The Agency Component must belong to the report Agency.';
        }
        if (isset($seen[$component->id()])) {
          $errors[$delta][] = 'Each Agency Component may have only one CSV upload.';
        }
        $seen[$component->id()] = TRUE;
      }
      if ($upload->get('field_request_data_csv')->isEmpty()) {
        $errors[$delta][] = 'Upload a CSV file for this Agency Component.';
      }
    }
    return $errors;
  }

}
