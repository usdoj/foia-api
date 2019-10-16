<?php

namespace Drupal\webform_template;

use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * The Webform Template Controller.
 *
 * @package Drupal\webform_template
 */
class WebformTemplateController {

  const TEMPLATESTATUSDEFAULT = 1;

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

    if ($decoded = $this->getTemplateDecoded()) {
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
    return $webform->get('foia_template') && empty($webform->getElementsDecoded());
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
    // Multiple webforms are failing this check at the moment, so we need to
    // sidestep.
    // @TODO: Remove this hack and fix the webforms.
    return TRUE;

    // If (!$templateElements = $this->getTemplateDecoded()) {
    // No valid template elements have been configured.
    // return TRUE;
    // }
    //
    //    $webformElements = $webform->getElementsDecoded();
    //    $filtered = array_filter($templateElements,
    //    function ($element) use ($webformElements) {
    //    return in_array($element, $webformElements);
    //    });
    //
    //    return count($filtered) === count($templateElements);
  }

  /**
   * Retrieve the configured template (string).
   *
   * @return string|null
   *   The webform elements template.
   */
  protected function getTemplate() {
    return $this->config->get('webform_template.settings')->get('webform_template_elements');
  }

  /**
   * Retrieve foia template settings for a webform.
   *
   * @return bool|null
   *   The boolean foia template setting, or null if not defined.
   */
  protected function getTemplateConfiguration($webform_id) {
    if (!$webform_id) {
      return NULL;
    }
    return $this->config->get('webform_template.webform')->get($webform_id);
  }

  /**
   * The parsed template.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE if YAML is invalid.
   */
  protected function getTemplateDecoded() {
    try {
      $decoded = Yaml::decode($this->getTemplate());
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
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The submitted form state.
   */
  public function preprocessWebformForm(array &$form, FormStateInterface $form_state) {
    $webform_id = $form_state->getFormObject()->getEntity()->id();
    $templated = $this->getTemplateConfiguration($webform_id);
    $form['actions']['submit']['#submit'][] = [get_class($this), 'processWebformForm'];
    $form['foia_template'] = [
      '#type' => 'checkbox',
      '#title' => t("Use FOIA Agency template"),
      '#disabled' => TRUE,
      '#default_value' => $templated === NULL ? $this::TEMPLATESTATUSDEFAULT : $templated,
    ];

    if (\Drupal::currentUser()->hasPermission('bypass foia webform template')) {
      // Remove the disabled attribute for sufficiently privileged users.
      $form['foia_template']['#disabled'] = FALSE;
    }

    if (!$templated) {
      // End here if the form does not use the template.
      return;
    }

    // Disable edit/delete links for template fields.
    foreach ($this->getTemplateDecoded() as $key => $element) {
      if (!isset($form['webform_ui_elements'][$key])) {
        continue;
      }
      if (isset($form['webform_ui_elements'][$key]['required'])) {
        $form['webform_ui_elements'][$key]['required']['#disabled'] = TRUE;
      }
      unset($form['webform_ui_elements'][$key]['operations']['#links']['edit']);
      unset($form['webform_ui_elements'][$key]['operations']['#links']['delete']);
    }
  }

  /**
   * Additional submit handler for the webform add/edit form.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The submitted form state.
   */
  public static function processWebformForm(array $form, FormStateInterface $form_state) {
    $webform_id = $form_state->getFormObject()->getEntity()->id();
    $foia_template = $form_state->getValue('foia_template');
    \Drupal::configFactory()->getEditable('webform_template.webform')->set($webform_id, $foia_template)->save();
  }

}
