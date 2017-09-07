<?php

namespace Drupal\foia_webform\Plugin\WebformHandler;

use Drupal\file\Entity\File;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Emails a webform submission.
 *
 * @Webformhandler(
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
    $data = $webform_submission->getData();

    // If there is a file attachment, get the file URL.
    if (isset($data['attachments_supporting_documentation'])) {
      $file = File::load($data['attachments_supporting_documentation']);
      $file_url = file_create_url($file->getFileUri());
      $data['attachments_supporting_documentation'] = $file_url;
    }

    // Format the submission values as CSV.
    $submissions = $this->arrayToString($data);

    $newline = ($message['html']) ? '<br/>' : "\n";
    $text = t('The submission data is reproduced below in CSV format for your convenience.');
    $message['body'] .= $text . $newline;
    $message['body'] .= '--------------------------' . $newline;
    $message['body'] .= $submissions;

    return parent::sendMessage($webform_submission, $message);
  }

  /**
   * Formats an array as a CSV string.
   *
   * @param array $data
   *   The form submission data.
   *
   * @return array
   *   Returns the array as a string.
   */
  public function arrayToString(array $data) {
    $handle = fopen('php://temp', 'w');
    // Use the array keys for the column headers.
    fputcsv($handle, array_keys($data));
    // Create the data row.
    fputcsv($handle, $data);
    // Reset the pointer to the beginning.
    fseek($handle, 0);
    // Read the stream into a string.
    $csv = stream_get_contents($handle);
    fclose($handle);

    return $csv;
  }

}
