<?php

namespace Drupal\webform_serialization\Plugin\jsonapi\FieldEnhancer;

use Drupal\Core\Serialization\Yaml;
use Drupal\jsonapi_extras\Plugin\ResourceFieldEnhancerBase;
use Shaper\Util\Context;
use Drupal\webform\Entity\WebformOptions;

/**
 * Decode YAML content.
 *
 * @ResourceFieldEnhancer(
 *   id = "webform_elements",
 *   label = @Translation("Webform Elements"),
 *   description = @Translation("Decode Webform elements configuration.")
 * )
 */
class WebformElementsEnhancer extends ResourceFieldEnhancerBase {

  /**
   * {@inheritdoc}
   */
  protected function doUndoTransform($data, Context $context) {
    $elements = Yaml::decode($data);
    $this->populateSelectFieldsWithOptions($elements);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function doTransform($data, Context $context) {
    $yaml = Yaml::encode($data);
    // @TODO: Do we need to do the reverse of populateSelectFieldsWithOptions?
    return $yaml;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutputJsonSchema() {
    return [
      'type' => 'object',
    ];
  }

  /**
   * Replaces webform option machine names with fully rendered list of options.
   *
   * @param array $elements
   *   Webform elements.
   */
  protected function populateSelectFieldsWithOptions(array &$elements) {
    foreach ($elements as &$element) {
      if ($element['#type'] === 'select' && !is_array($element['#options'])) {
        $selectElementOptions = WebformOptions::getElementOptions($element);
        $element['#options'] = $selectElementOptions;
      }
    }
  }

}
