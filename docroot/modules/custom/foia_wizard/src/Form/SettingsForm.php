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

    // Journey tab group.
    $form['journeys'] = [
      '#title' => $this->t('Journey List'),
      '#description' => $this->t('This is the list of journeys that will appear in the FOIA Request Wizard.'),
      '#type' => 'vertical_tabs',
    ];

    // Medical records tab.
    $form['journeys']['medical_records'] = [
      '#type' => 'details',
      '#title' => $this->t('Medical Records'),
      '#group' => 'journeys',
    ];
    $form['journeys']['medical_records']['questions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Questions'),
      '#default_value' => $this->config('foia_wizard.settings')->get('medical_records')['questions'],
      '#description' => $this->t('One question per line. Format: key|value. The keys are used by the FOIA.gov Wizard and should not be changed.'),
      '#group' => 'medical_records',
    ];
    $form['journeys']['medical_records']['messages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Messages'),
      '#default_value' => $this->config('foia_wizard.settings')->get('medical_records')['messages'],
      '#description' => $this->t('One message per line. Format: key|value. The keys are used by the FOIA.gov Wizard and should not be changed.'),
      '#group' => 'medical_records',
    ];
    $form['journeys']['medical_records']['results'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Result Slide'),
      '#default_value' => $this->config('foia_wizard.settings')->get('medical_records')['results'],
      '#description' => $this->t('Shown to users at the end of their journey.'),
      '#group' => 'medical_records',
    ];

    // Military records tab.
    $form['journeys']['military_records'] = [
      '#type' => 'details',
      '#title' => $this->t('Military Records'),
      '#group' => 'journeys',
    ];
    $form['journeys']['military_records']['questions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Questions'),
      '#default_value' => $this->config('foia_wizard.settings')->get('military_records')['questions'],
      '#description' => $this->t('One question per line. Format: key|value. The keys are used by the FOIA.gov Wizard and should not be changed.'),
      '#group' => 'military_records',
    ];
    $form['journeys']['military_records']['messages'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Messages'),
      '#default_value' => $this->config('foia_wizard.settings')->get('military_records')['messages'],
      '#description' => $this->t('One message per line. Format: key|value. The keys are used by the FOIA.gov Wizard and should not be changed.'),
      '#group' => 'military_records',
    ];
    $form['journeys']['military_records']['results'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Result Slide'),
      '#default_value' => $this->config('foia_wizard.settings')->get('military_records')['results'],
      '#description' => $this->t('Shown to users at the end of their journey.'),
      '#group' => 'military_records',
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
      ->set('query_slide', $form_state->getValue('query_slide'))
      ->set('medical_records', [
        'questions' => $form_state->getValue(['journeys', 'medical_records', 'questions']),
        'messages' => $form_state->getValue(['journeys', 'medical_records', 'messages']),
        'results' => $form_state->getValue(['journeys', 'medical_records', 'results']),
      ])
      ->set('military_records', [
        'questions' => $form_state->getValue(['journeys', 'military_records', 'questions']),
        'messages' => $form_state->getValue(['journeys', 'military_records', 'messages']),
        'results' => $form_state->getValue(['journeys', 'military_records', 'results']),
      ])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
