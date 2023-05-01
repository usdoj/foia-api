<?php

namespace Drupal\foia_wizard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure FOIA Request Wizard settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'foia_wizard_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['foia_wizard.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Add description markup to the form.
    $form['description'] = [
      '#markup' => $this->t('This form allows you to configure the FOIA Request Wizard.'),
    ];

    // Add a textarea for title that uses the Basic HTML format.
    $form['intro_slide'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Introduction Slide'),
      '#required' => TRUE,
      '#default_value' => $this->config('foia_wizard.settings')->get('intro_slide.value'),
      '#format' => $this->config('foia_wizard.settings')->get('intro_slide.format'),
      '#description' => $this->t('This is the text that will appear on the first slide of the FOIA Request Wizard.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save all the form fields.
    $this->config('foia_wizard.settings')
      ->set('intro_slide', $form_state->getValue('intro_slide'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
