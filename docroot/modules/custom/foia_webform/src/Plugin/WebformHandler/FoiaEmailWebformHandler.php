<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\Core\Render\Markup;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Dompdf\Dompdf;

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

    // Let webform do the heavy lifting in setting up the email.
    $message = parent::getMessage($webformSubmission);

    // Get form submission contents.
    $submissionContents = $webformSubmission->getData();
    $this->listFileAttachmentNamesInSubmission($submissionContents);
    $submissionMetadata = [
      'request_id' => $foiaRequestId,
      'confirmation_id' => $webformSubmission->id(),
    ];
    $submissionContents = array_merge($submissionMetadata, $submissionContents);

    // Build the message body.
    $bodySections = [
      t('Hello,'),
      t('A new FOIA request was submitted to your agency component:'),
      $this->formatSubmissionContentsAsList($submissionContents),
      $this->formatSubmissionContentsAsTable($submissionContents),
    ];
    $header = '<div><img style="width:70px;height:70px;"src="/img/foia-doj-logo.svg"/>FOIA Request ' . $foiaRequestI . '</div>';
    $message['body'] = implode('<br /><br />', $bodySections);
    // Create PDF file.
    $dompdf = new Dompdf();
    $dompdf->loadHtml($this->formatSubmissionContentsAsList($submissionContents));
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    $attachment = $dompdf->output();
    // file_put_contents('/sites/default/files/Brochure.pdf', $attachment);
    // Drupal::logger('foia_webform')->notice('$message = xxxxxx');
    // Attach PDF to email.
    $message['attachments'][] = [
      'filecontent' => $attachment,
      'filename' => 'FOIA Request ' . $foiaRequestId . '.pdf',
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
      switch ($this->getMailSystemSender()) {
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
   * Updates the submission contents to list the names of all file attachments.
   *
   * @param array &$submissionContents
   *   The submission contents.
   */
  protected function listFileAttachmentNamesInSubmission(array &$submissionContents) {
    $fileAttachmentElementsOnWebform = $this->getFileAttachmentElementsOnWebform();
    if ($fileAttachmentElementsOnWebform) {
      $this->updateSubmissionWithFileAttachmentNames($fileAttachmentElementsOnWebform, $submissionContents);
    }
  }

  /**
   * Gets the names of file attachment elements on the webform.
   *
   * @return array
   *   Returns an array of the names of the file attachment elements on the
   *   webform being submitted against.
   */
  protected function getFileAttachmentElementsOnWebform() {
    $fileAttachmentElementKeys = [];
    $webform = $this->getWebform();
    if ($webform->hasManagedFile()) {
      $elements = $webform->getElementsInitializedAndFlattened();
      foreach ($elements as $key => $element) {
        if (isset($element['#type']) && $element['#type'] === 'managed_file') {
          $fileAttachmentElementKeys[] = $key;
        }
      }
    }
    return $fileAttachmentElementKeys;
  }

  /**
   * Updates the submission contents with file attachment names.
   *
   * @param array $fileAttachmentElementKeys
   *   The keys of the file attachment webform elements.
   * @param array $submissionContents
   *   The submission contents.
   */
  protected function updateSubmissionWithFileAttachmentNames(array $fileAttachmentElementKeys, array &$submissionContents) {
    foreach ($fileAttachmentElementKeys as $fileAttachmentElementKey) {
      $fileAttachmentNames = [];
      $fids = isset($submissionContents[$fileAttachmentElementKey]) ? $submissionContents[$fileAttachmentElementKey] : '';
      if (empty($fids)) {
        continue;
      }
      /** @var \Drupal\file\FileInterface[] $files */
      $files = File::loadMultiple(is_array($fids) ? $fids : [$fids]);
      foreach ($files as $file) {
        $fileName = $file->getFilename();
        if ($file->hasField('field_virus_scan_status') && $file->get('field_virus_scan_status')->value === 'virus') {
          $fileName .= ': A virus was detected in this file and it was deleted.';
        }
        $fileAttachmentNames[] = $fileName;
      }
      $submissionContents[$fileAttachmentElementKey] = implode("\n", $fileAttachmentNames);
    }
  }

  /**
   * Formats the webform submission contents as a vertical list.
   *
   * @param array $submissionContents
   *   The webform submission contents.
   *
   * @return string
   *   Returns the submission contents as a human-readable list.
   */
  protected function formatSubmissionContentsAsList(array $submissionContents) {

    $table = [
      '#markup' => t('The following list contains the entire submission, and is formatted for ease of viewing and printing.'),
    ];
    $rows = [];
    foreach ($submissionContents as $key => $value) {
      $rows[] = [
        ['data' => ['#markup' => "<strong>$key</strong>"]],
        ['data' => $value],
      ];
    }
    $table['values'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#attributes' => ['width' => '500'],
    ];

    return \Drupal::service('renderer')->renderPlain($table);
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
  protected function formatSubmissionContentsAsTable(array $submissionContents) {
    $table = [
      '#markup' => t('The following table contains the entire submission, and is formatted for ease of copy/pasting into a spreadsheet.') . '<br /><br />',
    ];

    $table['values'] = [
      '#theme' => 'table',
      '#header' => array_keys($submissionContents),
      '#rows' => ['data' => $submissionContents],
    ];

    return \Drupal::service('renderer')->renderPlain($table);
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
