<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
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
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */
class FoiaEmailWebformHandler extends EmailWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'states' => [WebformSubmissionInterface::STATE_COMPLETED],
      'to_mail' => 'default',
      'to_options' => [],
      'cc_mail' => '',
      'cc_options' => [],
      'bcc_mail' => '',
      'bcc_options' => [],
      'from_mail' => 'default',
      'from_options' => [],
      'from_name' => 'default',
      'subject' => 'default',
      'body' => 'default',
      'excluded_elements' => [],
      'ignore_access' => FALSE,
      'exclude_empty' => TRUE,
      'html' => TRUE,
      'attachments' => TRUE,
      'debug' => FALSE,
      'reply_to' => '',
      'return_path' => '',
      'sender_mail' => '',
      'sender_name' => '',
    ];
  }

  /**
   * Gets the email message to send out to the agency component.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   * @param \Drupal\node\NodeInterface $agencyComponent
   */
  public function getEmailMessage(WebformSubmissionInterface $webformSubmission, NodeInterface $agencyComponent) {
    $message = parent::getMessage($webformSubmission);
    // Get form submissions.
    $data = $webformSubmission->getData();
    $errorMessage = NULL;
    $context = [];

    // Format the submission values as an HTML table.
    $formValuesAsTable = $this->arrayToTable($data);
    $message['body'] = $formValuesAsTable;

    $toEmail = $agencyComponent->get('field_submission_email')->value;

    if (!empty($toEmail)) {
      $message['to_mail'] = $toEmail;
    }
    // If we don't have a Submission Email value log an error.
    else {
      $errorMessage = 'No Submission Email: Unable to send email for %title';
      $context = [
        '%title' => $agencyComponent->getTitle(),
        'link' => $agencyComponent->toLink($this->t('Edit Component'), 'edit-form')->toString(),
      ];
    }


    // If the error message and context, log the error.
    if ($errorMessage && !empty($context)) {
      \Drupal::logger('foia_webform')
        ->error($errorMessage, $context);
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
