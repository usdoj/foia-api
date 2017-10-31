<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\Core\Render\Markup;
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
   * @param string $foiaRequestId
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   * @param string $componentEmailAddress
   *
   * @return array
   */
  public function getEmailMessage($foiaRequestId, WebformSubmissionInterface $webformSubmission, $componentEmailAddress) {
    // Let webform do the heavy lifting in setting up the email.
    $message = parent::getMessage($webformSubmission);

    // Get form submission contents.
    $submissionContents = $webformSubmission->getData();

    // Format the submission values as an HTML table.
    $submissionContentsAsTable = $this->formatSubmissionContentsAsTable($foiaRequestId, $submissionContents);
    $message['body'] = $submissionContentsAsTable;

    // Update the destination email address to the component's email address.
    $message['to_mail'] = $componentEmailAddress;

    return $message;
  }

  public function sendEmailMessage(WebformSubmissionInterface $webformSubmission, array $message) {
    $to = $message['to_mail'];
    $from = $message['from_mail'];

    // Remove less than (<) and greater (>) than from name.
    // @todo Figure out the proper way to encode special characters.
    // Note: PhpMail call.
    $message['from_name'] = preg_replace('/[<>]/', '', $message['from_name']);

    if (!empty($message['from_name'])) {
      $from = $message['from_name'] . ' <' . $from . '>';
    }

    $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    // Render body using webform email message (wrapper) template.
    $build = [
      '#theme' => 'webform_email_message_' . (($this->configuration['html']) ? 'html' : 'text'),
      '#message' => [
          'body' => is_string($message['body']) ? Markup::create($message['body']) : $message['body'],
        ] + $message,
      '#webform_submission' => $webformSubmission,
      '#handler' => $this,
    ];
    $message['body'] = trim((string) \Drupal::service('renderer')->renderPlain($build));

    if ($this->configuration['html']) {
      switch ($this->getMailSystemSender()) {
        case 'swiftmailer':
          // SwiftMailer requires that the body be valid Markup.
          $message['body'] = Markup::create($message['body']);
          break;
      }
    }

    // Send message.
    return $this->mailManager->mail('foia_webform', 'email_' . $this->getHandlerId(), $to, $current_langcode, $message, $from);
  }

  /**
   * Formats the webform submission contents as an HTML table.
   *
   * @param array $submissionContents
   *   The webform submission contents.
   *
   * @return string
   *   Returns the submission contents as an HTML table.
   */
  public function formatSubmissionContentsAsTable($foiaRequestId, array $submissionContents) {
    $tableHeaders = array_merge(['request_id'], array_keys($submissionContents));
    $tableRows = array_merge(['request_id' => $foiaRequestId], $submissionContents);
    $table = [
      '#markup' => t('Hello,') . '<br>' . t('A new FOIA request was submitted to your agency component:') . '<br><br>',
    ];

    $table['values'] = [
      '#theme' => 'table',
      '#header' => $tableHeaders,
      '#rows' => ['data' => $tableRows],
    ];

    return render($table);
  }

}
