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
   * Foia Template Array.
   *
   * @var array
   */
  public $templateElements;

  /**
   * WebformTemplateController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config service.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory;
    $this->templateElements = $this->getTemplateDecoded();
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
   * Webform custom validation for /admin/structure/webform/manage/{webform}.
   *
   * @param array $form
   *   Form object.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return void
   */
  public static function customvalidation(array $form, FormStateInterface $form_state) {

    $templateController = \Drupal::service('webform_template.template_controller');
    $result = $templateController->validation($form, $form_state);

    if ($result != '') {
      $form_state->setErrorByName('name', $result);
    }
  }

  /**
   * Webform custom validation for /admin/structure/webform/manage/{webform}
   *
   * @param array $templateElements, $webformElements
   *
   * @return t(message)
   */
  protected function validation(array $form, FormStateInterface $form_state) {
    $filtered = [];
    $diff_array = [];
    $result = '';
    $use_foia_template = $form_state->getValue('foia_template');
    if (!$templateElements = $this->getTemplateDecoded()) {
      // No valid template elements have been configured.
      return $result;
    }

    // If not use foia template, skip validation.
    if (!isset($use_foia_template) || ($use_foia_template == '0')) {
      return $result;
    }
    $webformElements = $form_state->getValues();

    // Validation skip if it is form setting.
    $form_id = isset($webformElements['form_id']) ? $webformElements['form_id'] : '';
    if ((strpos(strtolower($form_id), 'webform_settings') != FALSE) || !isset($webformElements['webform_ui_elements'])) {
      return $result;
    }

    $webformElements = isset($webformElements['webform_ui_elements']) ? array_keys($webformElements['webform_ui_elements']) : $webformElements;
    $templateElements = array_keys($templateElements);

    $webformElements = count(($webformElements)) ? $webformElements : $templateElements;

    if (count($webformElements) >= count($templateElements)) {
      $filtered = array_filter($webformElements, function ($element) use ($templateElements) {
        return !in_array(strtolower($element), array_map("strtolower", array_values($templateElements)));
      });
      $result = count($filtered) ? t('Error field(s): "@field". Please contact the site administrator.', ["@field" => implode(", ", $filtered)]) : $result;
    } else {

      // When webform elements count less than template elements count, find any difference first.
      $diff_array = array_diff($templateElements, $webformElements);
      // Find difference from template list.
      $filtered = array_filter($webformElements, function ($element) use ($templateElements) {
        return !in_array(strtolower($element), array_map("strtolower", array_values($templateElements)));
      });

      // Construck error messages.
      if (count($diff_array) && count($filtered)) {
        $result = t('Error field(s): "@filtered" and the following field(s) missing: "@diff". Please contact the site administrator.', ["@filtered" => implode(", ", $filtered), "@diff" => implode(", ", $diff_array)]);
      } else {
        if (count($diff_array)) {
          $result = t('The following field(s) missing: "@field". Please contact the site administrator.', ["@field" => implode(", ", $diff_array)]);
        }
        if (count($filtered)) {
          $result = t('Error field(s): "@field". Please contact the site administrator.', ["@field" => implode(", ", $filtered)]);
        }
      }
    }
    return $result;
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
   * Set the configured template (string).
   *
   * @param string $webform_id
   *   Webform id.
   *
   * @param int $value
   *   value = 0||1.
   *
   * @return void
   *   The boolean foia template setting, or null if not defined.
   */
  public function setTemplateConfiguration($webform_id, $value = 0) {
    if ($webform_id) {
      \Drupal::configFactory()->getEditable('webform_template.webform')->set($webform_id, $value)->save();
    }
  }

  /**
   * Retrieve foia template settings for a webform.
   *
   * @return bool|null
   *   The boolean foia template setting, or null if not defined.
   */
  public function getTemplateConfiguration($webform_id) {
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
    } catch (\Exception $exception) {
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
    $isNew = isset($form_state->getFormObject()->getEntity()) ? $form_state->getFormObject()->getEntity()->isNew() : FALSE;
    $templated = $this->getTemplateConfiguration($webform_id);
    $form['#validate'][] = [
      get_class($this),
      'customvalidation',
    ];
    // Add javascript file to ensure the correct templated value showing.
    $form['#attached'] = [
      'library' => [
        'webform_template/ajax_response_mgs',
      ],
      'drupalSettings' => [
        'var' => [
          'templated' => $isNew ? $isNew : $templated,
        ],
      ],
    ];

    $form['actions']['submit']['#submit'][] = [
      get_class($this),
      'processWebformForm',
    ];
    $form['foia_template'] = [
      '#type' => 'checkbox',
      '#title' => t("Use FOIA Agency template"),
      '#disabled' => TRUE,
      '#attributes' => [
        'id' => 'templated',
      ],
      '#default_value' => $templated ?? $this::TEMPLATESTATUSDEFAULT,
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
    // Check if the webform elements match the foia tamplate,
    // set use foia agency element checkbox, otherwise uncheck.
    $templateController = \Drupal::service('webform_template.template_controller');
    $result = $templateController->validation($form, $form_state);
    if ($result != '') {
      $templateController->setTemplateConfiguration($webform_id);
    }
    else {
      $templateController->setTemplateConfiguration($webform_id, $foia_template);
    }
  }

}
