<?php

namespace Drupal\webform_template;

use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\DatabaseException;

/**
 * The Webform Template Controller.
 *
 * @package Drupal\webform_template
 */
class WebformTemplateController {

  const TEMPLATESTATUSDEFAULT = 1;
  const DEFAULT_FIELDS_WILL_BE_ADDED = 'default fields will be added';
  const CURRENT_TEMPLATE_STATUS_CHOICE = 'current template status choice';

  /**
   * Config service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Database connection service.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * WebformTemplateController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, Connection $database) {
    $this->config = $configFactory;
    $this->db = $database;
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

    if ($webform->getState($this::DEFAULT_FIELDS_WILL_BE_ADDED)) {
      $webform->deleteState($this::DEFAULT_FIELDS_WILL_BE_ADDED);
      return TRUE;
    }

    $templated = $this->getTemplateConfiguration($webform->id());

    if ($webform->hasState($this::CURRENT_TEMPLATE_STATUS_CHOICE)) {
      $templated = $webform->getState($this::CURRENT_TEMPLATE_STATUS_CHOICE);
      $webform->deleteState($this::CURRENT_TEMPLATE_STATUS_CHOICE);
    }

    if (!$templated) {
      return TRUE;
    }

    if (!$templateElements = $this->getTemplateDecoded()) {
      // No valid template elements have been configured.
      return TRUE;
    }

    $templateElements = array_keys($templateElements);
    return $this->validation($templateElements, $webform);
  }

  /**
   * Do validation.
   *
   * @param array $templateElements
   *   Template elements.
   * @param object $webform
   *   The webform.
   *
   * @return bool
   *   TRUE if the webform contains all elements defined on the template.
   */
  public function validation(array $templateElements, $webform) {
    $result = FALSE;

    $webformElements = $webform->getElementsDecoded();
    $webformElements = array_keys($webformElements);
    $filtered = array_filter($templateElements, function ($element) use ($webformElements) {
      return in_array($element, $webformElements);
    });
    $result = count($filtered) === count($templateElements);
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
   * Retrieve foia template settings from database.
   *
   * @return bool|null
   *   The boolean foia template setting, or null if not defined.
   */
  public function getUseTemplateCheckBoxConfiguration($webform_id) {
    $templated = '0';
    if (!$webform_id) {
      return $templated;
    }
    try {
      $query = $this->db->select('config', 'n');
      $query->fields('n');
      $query->condition('n.name', 'webform_template.webform');
      $data = $query->execute()->fetchAll();
      $data = $data[0]->data;
      $data_array = unserialize($data);
      $templated = $data_array[$webform_id] ?? $templated;
    }
    catch (DatabaseException $e) {
      watchdog_exception('get config use template flg error.', $e);
    }
    return $templated;
  }

  /**
   * The parsed template.
   *
   * @return array|bool
   *   Elements as an associative array. Returns FALSE if YAML is invalid.
   */
  public function getTemplateDecoded() {
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
    $form['actions']['submit']['#submit'][] = [
      get_class($this),
      'processWebformForm',
    ];
    array_unshift($form['actions']['submit']['#submit'], [
      get_class($this),
      'processWebformFormBeforeSave',
    ]);
    $form['foia_template'] = [
      '#type' => 'checkbox',
      '#title' => t("Use FOIA Agency template"),
      '#disabled' => TRUE,
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
      // If foia template in use, diable edit link on form elements.
      $title = $form['webform_ui_elements'][$key]['title']['link']['#title'];
      $attributes = $form['webform_ui_elements'][$key]['title']['link']['#attributes'];
      $prefix = $form['webform_ui_elements'][$key]['title']['link']['#prefix'];
      unset($form['webform_ui_elements'][$key]['title']['link']);
      $form['webform_ui_elements'][$key]['title']['text']['#type'] = 'label';
      $form['webform_ui_elements'][$key]['title']['text']['#title'] = $title;
      $form['webform_ui_elements'][$key]['title']['text']['#attributes'] = $attributes;
      $form['webform_ui_elements'][$key]['title']['text']['#prefix'] = $prefix;
      unset($form['webform_ui_elements'][$key]['operations']['#links']['edit']);
      unset($form['webform_ui_elements'][$key]['operations']['#links']['delete']);
    }
  }

  /**
   * Additional submit handler to set some state variables before saving.
   *
   * @param array $form
   *   The form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The submitted form state.
   */
  public static function processWebformFormBeforeSave(array $form, FormStateInterface $form_state) {
    $webform = $form_state->getFormObject()->getEntity();
    $templated = $form_state->getValue('foia_template');
    $templateController = \Drupal::service('webform_template.template_controller');
    $webform->setState($templateController::CURRENT_TEMPLATE_STATUS_CHOICE, $templated);

    if ($webform->isNew() && $templated) {
      $webform->setState($templateController::DEFAULT_FIELDS_WILL_BE_ADDED, TRUE);
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
