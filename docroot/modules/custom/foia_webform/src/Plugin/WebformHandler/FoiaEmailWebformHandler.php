<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\Core\Render\Markup;
use Drupal\node\NodeInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\foia_webform\FoiaSubmissionPrettyFormatter;

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
   * The agency component node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $agencyComponent;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaultConfiguration = parent::defaultConfiguration();
    $defaultConfiguration['attachments'] = TRUE;
    return $defaultConfiguration;
  }

  /**
   * Get configuration default values.
   *
   * @return array
   *   Configuration default values.
   */
  protected function getDefaultConfigurationValues() {
    $defaultValues = parent::getDefaultConfigurationValues();
    $defaultValues['subject'] = $this->getEmailSubject();
    $defaultValues['to_mail'] = $this->agencyComponent->get('field_submission_email')->value;

    return $defaultValues;
  }

  /**
   * Gets the email message to send out to the agency component.
   *
   * @param string $foiaRequestId
   *   The id of the FOIA request to include in the email.
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The agency component.
   *
   * @return array
   *   The email to send to the agency component.
   */
  public function getEmailMessage($foiaRequestId, WebformSubmissionInterface $webformSubmission, NodeInterface $agencyComponent) {
    $this->agencyComponent = $agencyComponent;

    // Our custom class for generating pretty output from submission data.
    $formatter = new FoiaSubmissionPrettyFormatter($foiaRequestId, $webformSubmission);

    // Let webform do the heavy lifting in setting up the email.
    $message = parent::getMessage($webformSubmission);

    // Build the message body.
    $bodySections = [
      t('Hello,'),
      t('A new FOIA request was submitted to your agency component:'),
      $formatter->formatSubmissionContentsAsList(),
      $formatter->formatSubmissionContentsAsTable(),
    ];
    $message['body'] = implode('<br /><br />', $bodySections);

    // Attach PDF version to email.
    $message['attachments'][] = [
      'filecontent' => $formatter->formatSubmissionContentsAsPdf(),
      'filename' => 'FOIA Request confirmation #' . $webformSubmission->id() . '.pdf',
      'filemime' => 'application/pdf',
    ];
    return $message;
  }

  /**
   * Sends the email message to the appropriate component.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission.
   * @param array $message
   *   The email message to send.
   *
   * @return array
   *   The $message array structure containing all details of the message. If
   *   already sent ($send = TRUE), then the 'result' element will contain the
   *   success indicator of the email, failure being already written to the
   *   watchdog. (Success means nothing more than the message being accepted at
   *   php-level, which still doesn't guarantee it to be delivered.)
   *
   * @see \Drupal\Core\Mail\MailManagerInterface::mail()
   */
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
      '#message' =>
        [
          'body' => is_string($message['body']) ? Markup::create($message['body']) : $message['body'],
        ] + $message,
      '#webform_submission' => $webformSubmission,
      '#handler' => $this,
    ];
    $message['body'] = trim((string) \Drupal::service('renderer')->renderPlain($build));

    if ($this->configuration['html']) {
      switch ($this->getMailSystemFormatter()) {
        case 'swiftmailer':
          // SwiftMailer requires that the body be valid Markup.
          $message['body'] = Markup::create($message['body']);
          break;
      }
    }

    // Send message.
    return $this->mailManager->mail('webform', 'email_' . $this->getHandlerId(), $to, $current_langcode, $message, $from, TRUE);
  }

  /**
   * Returns the subject to use for the email.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The email subject.
   */
  protected function getEmailSubject() {
    return t('New FOIA request received for @agency_component_name', ['@agency_component_name' => $this->agencyComponent->label()]);
  }

}
