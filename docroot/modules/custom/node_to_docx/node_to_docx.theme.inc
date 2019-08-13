<?php

/**
 * @file
 * 
 * Preprocessors and theme functions of Node to docx module.
 */

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\Element;

/**
 * Set variables
 *
 * Default template: node--node-to-docx.html.twig
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 */
function template_preprocess_node_to_docx(&$variables) {
  $node = $variables['elements']['#node'];
  // $content variable used in the template
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}