<?php

namespace Drupal\foia_annual_data_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure foia_annual_data_report settings for this site.
 *
 * @todo Validate the memory limit setting. Int or int followed by K, M, or G.
 */
class MemoryLimitForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'foia_annual_data_report_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['foia_annual_data_report.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['annual_report_memory_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Annual Report Memory Limit'),
      '#default_value' => $this->config('foia_annual_data_report.settings')->get('annual_report_memory_limit'),
      '#description' => $this->t('Leave blank for no override. If set, make sure this value is valid per PHPs memory limit options.'),
    ];
    $form['debug_annual_report_memory_limit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug Annual Report memory limit'),
      '#default_value' => $this->config('foia_annual_data_report.settings')->get('debug_annual_report_memory_limit'),
      '#description' => $this->t('When set `debug` entries will be written to log files to verify the module is working.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('foia_annual_data_report.settings')
      ->set('annual_report_memory_limit', $form_state->getValue('annual_report_memory_limit'))
      ->save();
    $this->config('foia_annual_data_report.settings')
      ->set('debug_annual_report_memory_limit', $form_state->getValue('debug_annual_report_memory_limit'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
