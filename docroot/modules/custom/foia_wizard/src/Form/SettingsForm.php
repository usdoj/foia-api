<?php

namespace Drupal\foia_wizard\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure FOIA Request Wizard settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  const M_COUNT = 60;

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

    // Hacky but huge UX improvement
    $form['CSS'] = [
      '#children' => '<style>.foia-wizard-settings .vertical-tabs__menu {max-height: 30rem; overflow-y: auto; overflow-x:hidden}</style>',
    ];

    $form['#tree'] = TRUE;

    // Introduction title slide.
    $form['intro_slide'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Introduction Slide'),
      '#required' => TRUE,
      '#default_value' => $this->config('foia_wizard.settings')->get('intro_slide.value'),
      '#format' => $this->config('foia_wizard.settings')->get('intro_slide.format'),
      '#description' => $this->t('This is the text that will appear on the first slide of the FOIA Request Wizard.'),
    ];

    // Beginning query slide.
    $form['query_slide'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Query Slide'),
      '#required' => TRUE,
      '#default_value' => $this->config('foia_wizard.settings')->get('query_slide.value'),
      '#format' => $this->config('foia_wizard.settings')->get('query_slide.format'),
      '#description' => $this->t('This is the text that will appear on the query slide of the FOIA Request Wizard.'),
    ];

    // Messages tab group.
    $form['message_tabs'] = [
      '#title' => '<h3>' . $this->t('Message List') . '</h3>',
      '#description' => $this->t('This text will be used within the FOIA Request Wizard.'),
      '#type' => 'vertical_tabs',
    ];

    // Add rich text fields for messages.
    for ($i = 1; $i <= self::M_COUNT; $i++) {
      $form['message_tabs']['messages' . $i] = [
        '#type' => 'details',
        '#title' => $this->t('Message @i', ['@i' => $i]),
        '#group' => 'message_tabs',
      ];
      $form['message_tabs']['messages' . $i]['m' . $i] = [
        '#type' => 'text_format',
        '#title' => $this->t('Message @i', ['@i' => $i]),
        '#default_value' => $this->config('foia_wizard.settings')->get('messages')['m' . $i]['value'],
        '#group' => 'messages' . $i,
        '#format' => $this->config('foia_wizard.settings')->get('messages')['m' . $i]['format'],
        '#description' => $this->t('This text will be used as key <code>m@i</code> within the FOIA Request Wizard.', ['@i' => $i]),
      ];
    }

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

    // Collect message field values.
    $messages = [];
    for ($i = 1; $i <= self::M_COUNT; $i++) {
      $messages['m' . $i] = $form_state->getValue([
        'message_tabs',
        'messages' . $i,
        'm' . $i,
      ]);
    }

    // Save all the form fields.
    $this->config('foia_wizard.settings')
      ->set('intro_slide', $form_state->getValue('intro_slide'))
      ->set('query_slide', $form_state->getValue('query_slide'))
      ->set('messages', $messages)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
