<?php

namespace Drupal\webform_serialization\Normalizer;

use Drupal\jsonapi\Normalizer\ConfigEntityNormalizer as JsonapiConfigEntityNormalizer;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\WebformOptions;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Converts a webform into JSON API array structure.
 */
class WebformNormalizer extends JsonapiConfigEntityNormalizer {

  protected $supportedInterfaceOrClass = Webform::class;

  /**
   * {@inheritdoc}
   */
  public function getFields($entity, $bundle, ResourceType $resource_type) {
    $enabled_public_fields = parent::getFields($entity, $bundle, $resource_type);
    if (!empty($enabled_public_fields['elements'])) {
      try {
        $parsed = Yaml::decode($enabled_public_fields['elements']);
        $this->populateSelectFieldsWithOptions($parsed);
        $enabled_public_fields['elements'] = $parsed;
      }
      catch (\Exception $exception) {
        throw new UnprocessableEntityHttpException(
          sprintf('There was an error parsing the webform configuration. Message: %s.', $exception->getMessage()),
          $exception
        );
      }
    }

    return $enabled_public_fields;
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
