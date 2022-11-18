<?php

namespace Drupal\foia_request\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\foia_request\Entity\FoiaRequest;

/**
 * Purge failed FOIA requests older than 2 weeks.
 */
class FoiaRequestPurgeForm extends FormBase {

  public function getFormId() {
    return 'foia_request_purge_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $requests = $this->queryRequests();
    $form['info']['#markup'] = '<p>There are currently ' . count($requests) . ' failed requests dated more than 2 weeks ago.</p>';
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete these requests permanently'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  private function queryRequests() {
    $cutoff_date = strtotime('-2 weeks');
    return \Drupal::entityQuery('foia_request')
      ->condition('request_status', FoiaRequestInterface::STATUS_FAILED)
      ->condition('created', $cutoff_date, '<')
      ->execute();
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $requests = $this->queryRequests();
    $total_deleted = 0;
    foreach (array_keys($requests) as $request_id) {
      $request = FoiaRequest::load($request_id);
      $request->delete();
      $total_deleted += 1;
    }
    \Drupal::messenger()->addMessage('Total requests purged: ' . $total_deleted);
  }

}
