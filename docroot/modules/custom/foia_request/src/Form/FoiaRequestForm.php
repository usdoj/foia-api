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
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\foia_request\Entity\FoiaRequest */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label FOIA Request.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label FOIA Request.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.foia_request.canonical', ['foia_request' => $entity->id()]);
  }

}
