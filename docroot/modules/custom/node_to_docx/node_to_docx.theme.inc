<?php

/**
 * @file
 * Preprocessors and theme functions of Node to docx module.
 */

use Drupal\Core\Render\Element;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Set variables.
 *
 * Default template: node--node-to-docx.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An array of elements to display in view mode.
 */
function template_preprocess_node_to_docx(array &$variables) {
  $node = $variables['elements']['#node'];
  // Helpful $content variable for templates.
  $variables += [
    'content' => [],
  ];
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  // It seems unusual that we would have to write code to add these, shouldn't
  // they already be in the content variable ?
  if ($node) {
    if ($node instanceof Node) {
      $variables += [
        'docx' => [],
      ];
      $field_names = array_keys($node->getFields());
      foreach ($field_names as $field_name) {
        if ($node->hasField($field_name)) {
          $field_config = FieldStorageConfig::loadByName('node', $field_name);
          if (($field_config != NULL) && $field_config->getCardinality() == '-1') {
            for ($i = 0; $i < $node->get($field_name)->count(); $i++) {
              $variables['docx'][$field_name][] = $node->get($field_name)[$i]->value;
            }
          }
          else {
            $variables['docx'][$field_name] = $node->get($field_name)->value;
          }
        }
      }
    }
  }
}

/**
 * Duplicate template variables for quarterly reports.
 */
function template_preprocess_node_to_docx_quarterly(array &$variables) {
  template_preprocess_node_to_docx($variables);
}
