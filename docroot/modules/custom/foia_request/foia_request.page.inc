<?php

/**
 * @file
 * Contains foia_request.page.inc.
 *
 * Page callback for FOIA Request entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for FOIA Request templates.
 *
 * Default template: foia_request.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_foia_request(array &$variables) {
  // Fetch FoiaRequest Entity Object.
  $foia_request = $variables['elements']['#foia_request'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
