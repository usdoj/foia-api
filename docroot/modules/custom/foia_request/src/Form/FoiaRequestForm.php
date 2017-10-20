<?php

namespace Drupal\foia_request\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for FOIA Request edit forms.
 *
 * @ingroup foia_request
 */
class FoiaRequestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the FOIA Request.'));
        break;

      default:
        drupal_set_message($this->t('Saved the FOIA Request.'));
    }
    $form_state->setRedirect('entity.foia_request.canonical', ['foia_request' => $entity->id()]);
  }

}
