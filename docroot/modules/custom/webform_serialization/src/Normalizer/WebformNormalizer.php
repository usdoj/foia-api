<?php

namespace Drupal\webform_serialization\Normalizer;

use Drupal\jsonapi\Normalizer\ConfigEntityNormalizer as JsonapiConfigEntityNormalizer;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\webform\Entity\Webform;

/**
 * Converts a webform into JSON API array structure.
 */
class WebformNormalizer extends JsonapiConfigEntityNormalizer {

  protected $supportedInterfaceOrClass = Webform::class;

  /**
   * {@inheritdoc}
   */
  public function getFields($entity, $bundle, ResourceType $resource_type) {
    return parent::getFields($entity, $bundle, $resource_type);
  }

}
