<?php

namespace Drupal\foia_upload_xml\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\foia_upload_xml\FoiaUploadBatchExecutable;

/**
 * Class BatchUploadForm.
 *
 * @package Drupal\foia_upload_xml\Form
 *
 * Provides form to test the batch import migrations functionality.
 */
class BatchUploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    // TODO: Implement getFormId() method.
    return 'batchuploadform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildForm() method.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Execute Batch Import'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
    $executable = new FoiaUploadBatchExecutable($migration_plugin, $migrateMessage, $options);
    $executable->batchImport();

    $form_state->setRedirect('entity.migration.list', ['migration_group' => 'foia_xml']);
  }

}
