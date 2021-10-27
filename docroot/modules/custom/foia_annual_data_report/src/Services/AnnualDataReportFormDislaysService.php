<?php

namespace Drupal\foia_annual_data_report\Services;

/**
 * Class AnnualDataReportFormDislaysService for services for annual data.
 */
class AnnualDataReportFormDislaysService {

  /**
   * Returns information about tha Annual Data Report (adr) form displays.
   *
   * @return array
   *   Info about the form display.
   */
  public function info($form_id): array {

    // Initialize an array used to return values.
    $info = [
      'is_adr_form' => FALSE,
      'form_section' => '',
      'form_nav' => '',
    ];

    // Grab the all form display modes for nodes.
    $modes_full = \Drupal::service('entity_display.repository')->getFormModes('node');

    // Just need the array keys.
    $modes_keys = array_keys($modes_full);

    // Initialize a working array that will hold form displays for adr.
    $modes = [];

    // Strip these from the label.
    $strip_tags = [
      'Annual Report - ',
    ];

    // Loop through all form displays for all nodes.
    foreach ($modes_keys as $mode_key) {
      // Only grab the form displays for annual reports.
      if (strpos($mode_key, 'annual_report') === 0) {
        $modes[$mode_key] = str_replace($strip_tags, '', $modes_full[$mode_key]['label']);
      }
    }

    // Grab the last part of the current path, and treat as the current mode.
    $current_path = \Drupal::service('path.current')->getPath();
    $current_path_ar = explode('/', $current_path);
    $current_mode = array_pop($current_path_ar);

    // Sort by machine name.
    ksort($modes);

    // Create an array of form ids that would indicate this is an adr form.
    $adr_form_ids = [
      'node_annual_foia_report_data_' . $current_mode . '_form',
    ];

    // If this path is in the list of modes then modify for annual report.
    if (in_array($form_id, $adr_form_ids)) {

      // Use this part of the path to append the nav form mode to.
      $path_part = implode('/', $current_path_ar);

      // Use this to help get the prev/next links.
      $iterator = new \ArrayIterator($modes);

      // Initialize for nav html.
      $form_nav = '';

      // "Dropdown" nav with all form modes.
      $form_nav .= '<ul class="form-section-nav">';
      foreach ($modes as $key => $label) {
        $li_tag = '<li>';
        if ($key === $current_mode) {
          $li_tag = '<li class="is-active">';
        }
        $form_nav .= $li_tag . '<a href="' . $path_part . '/' . $key . '">';
        $form_nav .= $label . '</a></li>';
      }
      $form_nav .= '</ul>';

      // Position of the mode in the array.
      $mode_pos = array_search($current_mode, array_keys($modes));

      // If the mode is not found in the mode array, this must be the default.
      if (!$mode_pos && $current_mode === 'edit') {

        // Default view should link to first "mode".
        $iterator->seek(0);
        $form_nav .= '<a href="' . $path_part;
        $form_nav .= '/edit/' . $iterator->key() . '">';
        $form_nav .= $iterator->current() . ' ---&gt;</a>';

      }

      else {

        // Grab the previous link if not at the start.
        if ($mode_pos > 0) {
          $iterator->seek($mode_pos - 1);
          $form_nav .= '<a href="' . $path_part;
          $form_nav .= '/' . $iterator->key() . '">';
          $form_nav .= '&lt;--- ' . $iterator->current() . '</a>';
        }
        elseif ($mode_pos === 0) {
          $form_nav .= '<a href="' . $path_part . '">';
          $form_nav .= '&lt;--- Default/Full Mode</a>';
        }

        // Divider for both links.
        if ($mode_pos >= 0 && $mode_pos < $iterator->count() - 1) {
          $form_nav .= '&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;';
        }

        // Grab the next link if not at the end.
        if ($mode_pos < $iterator->count() - 1) {
          $iterator->seek($mode_pos + 1);
          $form_nav .= '<a href="' . $path_part;
          $form_nav .= '/' . $iterator->key() . '">';
          $form_nav .= $iterator->current() . ' ---&gt;</a>';
        }

      }

      // Set the return info.
      $info = [
        'is_adr_form' => TRUE,
        'form_section' => $current_mode,
        'form_nav' => $form_nav,
      ];

    }

    // Return array with form display information.
    return $info;

  }

}
