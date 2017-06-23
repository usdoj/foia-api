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

    if ($decoded = $this->getTemplateConfigurationDecoded()) {
      $editable = Webform::load($webform->id());
      $editable->setElements($decoded);
      $editable->save();
    }

  }

  /**
   * Determine if the webform can safely inherit default elements.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return bool
   *   TRUE if the webform has no fields defined.
   */
  protected static function canApplyWebformTemplate(WebformInterface $webform) {
    return empty($webform->getElementsDecoded());
  }

  /**
   * Determine if the webform contains the fields required by its template.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   *
   * @return bool
   *   TRUE if the webform contains all elements defined on the template.
   */
  public function webformImplementsTemplate(WebformInterface $webform) {
    if (!$templateElements = $this->getTemplateConfigurationDecoded()) {
      // No valid template elements have been configured.
      return TRUE;
    }

    $webformElements = $webform->getElementsDecoded();
    $filtered = array_filter($templateElements, function ($element) use ($webformElements) {
      return in_array($element, $webformElements);
    });

    return count($filtered) === count($templateElements);
  }

  /**
   * Retrieve the configured template (string).
   *
   * @return string|null
   *   The webform elements template.
   */
  protected function getTemplateConfiguration() {
    return $this->config->get('webform_template.settings')->get('webform_template_elements');
  }

  /**
   * Parsed template configuration.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE if YAML is invalid.
   */
  protected function getTemplateConfigurationDecoded() {
    try {
      $decoded = Yaml::decode($this->getTemplateConfiguration());
      return $decoded;
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * Remove edit/delete links from templated elements.
   *
   * @param array $form
   *   The form render array.
   */
  public function preprocessWebformForm(array &$form) {
    foreach ($this->getTemplateConfigurationDecoded() as $key => $element) {
      if (!isset($form['webform_ui_elements'][$key])) {
        continue;
      }
      unset($form['webform_ui_elements'][$key]['operations']['#links']['edit']);
      unset($form['webform_ui_elements'][$key]['operations']['#links']['delete']);
    }
  }

}
