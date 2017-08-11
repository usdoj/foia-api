<?php

/**
 * @file
 * Contains foia_personnel.page.inc.
 *
 * Page callback for FOIA Personnel entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for FOIA Personnel templates.
 *
 * Default template: foia_personnel.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_foia_personnel(array &$variables) {
  // Fetch FoiaPersonnel Entity Object.
  $foia_personnel = $variables['elements']['#foia_personnel'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
