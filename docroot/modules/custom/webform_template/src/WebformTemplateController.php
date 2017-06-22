<?php

namespace Drupal\webform_template;

use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * The Webform Template Controller.
 *
 * @package Drupal\webform_template
 */
class WebformTemplateController {

  /**
   * Config service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * WebformTemplateController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory;
  }

  /**
   * Add default fields to a webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   */
  public function addDefaultFields(WebformInterface $webform) {
    if (!self::canApplyWebformTemplate($webform)) {
      return;
    }

    $config = $this->config->get('webform_template.settings')->get('webform_template_elements');
    try {
      $decoded = Yaml::decode($config);
      $editable = Webform::load($webform->id());
      $editable->setElements($decoded);
      $editable->save();
    }
    catch (\Exception $exception) {
      // @todo: handle yaml parse exception.
    }
  }

  /**
   * Ensure the webform can safely inherit default elements.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return bool
   *   Status.
   */
  protected static function canApplyWebformTemplate(WebformInterface $webform) {
    return empty($webform->getElementsDecoded());
  }

}
