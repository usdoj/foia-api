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
        $this->getSelectFields($parsed);
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
   * Add the options for select items if the options are stored in config files.
   *
   * @param array $formItems
   *   The already parsed form array.
   */
  private function getSelectFields(array &$formItems) {
    foreach ($formItems as $key => $element) {
      if ($element['#type'] === 'select' && !is_array($element['#options'])) {
        /** @var \Drupal\webform\Entity\WebformOptions $webformOptions */
        $webformOptions = WebformOptions::getElementOptions($element);
        $formItems[$key]['#options'] = $webformOptions;
      }
    }
  }

}
