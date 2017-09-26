<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\file\Entity\File;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\node\Entity\Node;

/**
 * Emails a webform submission.
 *
 * @WebformHandler(
 *   id = "foia_email",
 *   label = @Translation("FOIA email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission to appropriate the agency."),
 *   cardinality = \Drupal\webform\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class FoiaEmailWebformHandler extends EmailWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {
    // Get form submissions.
    $data = $webform_submission->getData();
    $error_message = NULL;
    $context = [];

    // If there is a file attachment, get the file URL.
    if (isset($data['attachments_supporting_documentation'])) {
      $files = [];
      foreach ($data['attachments_supporting_documentation'] as $upload) {
        $file = File::load($upload);
        if ($file) {
          $file_url = file_create_url($file->getFileUri());
          $files[] = $file_url;
        }
      }
      if (!empty($files)) {
        $data['attachments_supporting_documentation'] = implode(',', $files);
      }
    }

    // Format the submission values as an HTML table.
    $form_values_as_table = $this->arrayToTable($data);
    $message['body'] = $form_values_as_table;

    // Look up the agency component.
    $form = $webform_submission->getWebform();
    $form_id = $form->getOriginalId();
    $agency_component = $this->lookupComponent($form_id);

    // If we have an Agency Component, get the Submission Email value.
    if ($agency_component) {
      $to_email = $agency_component->get('field_submission_email')->getValue();

      if (!empty($to_email)) {
        $message['to_mail'] = $to_email[0]['value'];
      }
      // If we don't have a Submission Email value log an error.
      else {
        $error_message = 'No Submission Email: Unable to send email for %title';
        $context = [
          '%title' => $agency_component->getTitle(),
          'link' => $agency_component->toLink($this->t('Edit Component'), 'edit-form')->toString(),
        ];
      }
    }
    // If there isn't an associated Agency Component log an error.
    else {
      $error_message = 'Unassociated form: The form, %title, is not associated with an Agency Component.';
      $context = [
        '%title' => $form->label(),
      ];
    }

    // If the error message and context, log the error.
    if ($error_message && !empty($context)) {
      \Drupal::logger('foia_webform')
        ->error($error_message, $context);
    }
    // Send the email.
    else {
      return parent::sendMessage($webform_submission, $message);
    }
  }

  /**
   * Formats an array as an HTML table.
   *
   * @param array $data
   *   The form submission data.
   *
   * @return string
   *   Returns the array as an HTML table.
   */
  public function arrayToTable(array $data) {
    $table = [
      '#markup' => t('Hello,') . '<br>' . t('A new FOIA request was submitted to your agency component:') . '<br><br>',
    ];

    $table['values'] = [
      '#theme' => 'table',
      '#header' => array_keys($data),
      '#rows' => ['data' => (array) $data],
    ];

    return render($table);
  }

  /**
   * Queries for an associated Agency Component given a form ID.
   *
   * @param string $form_id
   *   The form ID.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The Agency Component object or NULL.
   */
  public function lookupComponent($form_id) {
    $entity_query_service = \Drupal::service('entity.query');
    $query = $entity_query_service->get('node')
      ->condition('type', 'agency_component')
      ->condition('field_request_submission_form', $form_id);
    $nid = $query->execute();

    $node = ($nid) ? Node::load(reset($nid)) : NULL;
    return $node;
  }

}
