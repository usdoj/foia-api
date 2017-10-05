<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\file\Entity\File;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;

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
  public function sendMessage(WebformSubmissionInterface $webformSubmission, array $message) {
    // Get form submissions.
    $data = $webformSubmission->getData();
    $errorMessage = NULL;
    $context = [];

    // If there is a file attachment, get the file URL.
    if (isset($data['attachments_supporting_documentation'])) {
      $files = [];
      foreach ($data['attachments_supporting_documentation'] as $upload) {
        $file = File::load($upload);
        if ($file) {
          $fileUrl = file_create_url($file->getFileUri());
          $files[] = $fileUrl;
        }
      }
      if (!empty($files)) {
        $data['attachments_supporting_documentation'] = implode(',', $files);
      }
    }

    // Format the submission values as an HTML table.
    $formValuesAsTable = $this->arrayToTable($data);
    $message['body'] = $formValuesAsTable;

    // Look up the agency component.
    $form = $webformSubmission->getWebform();
    $webformId = $form->getOriginalId();
    $agencyLookupService = \Drupal::service('foia_webform.agency_lookup_service');
    $agencyComponent = $agencyLookupService->getComponentFromWebform($webformId);

    // If we have an Agency Component, get the Submission Email value.
    if ($agencyComponent) {
      $toEmail = $agencyComponent->get('field_submission_email')->getValue();

      if (!empty($toEmail)) {
        $message['to_mail'] = $toEmail[0]['value'];
      }
      // If we don't have a Submission Email value log an error.
      else {
        $errorMessage = 'No Submission Email: Unable to send email for %title';
        $context = [
          '%title' => $agencyComponent->getTitle(),
          'link' => $agencyComponent->toLink($this->t('Edit Component'), 'edit-form')->toString(),
        ];
      }
    }
    // If there isn't an associated Agency Component log an error.
    else {
      $errorMessage = 'Unassociated form: The form, %title, is not associated with an Agency Component.';
      $context = [
        '%title' => $form->label(),
      ];
    }

    // If the error message and context, log the error.
    if ($errorMessage && !empty($context)) {
      \Drupal::logger('foia_webform')
        ->error($errorMessage, $context);
    }
    // Send the email.
    else {
      return parent::sendMessage($webformSubmission, $message);
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

}
