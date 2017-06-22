<?php

namespace Drupal\webform_serialization\Normalizer;

use Drupal\jsonapi\Normalizer\ConfigEntityNormalizer as JsonapiConfigEntityNormalizer;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Serialization\Yaml;
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

}
