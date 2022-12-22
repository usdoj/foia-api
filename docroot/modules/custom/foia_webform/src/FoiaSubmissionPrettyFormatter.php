<?php

namespace Drupal\foia_webform;

use Drupal\webform\WebformSubmissionInterface;
use Dompdf\Dompdf;
use Drupal\file\Entity\File;

/**
 * Class FoiaSubmissionPrettyFormatter.
 *
 * Formats the submission data in human-readable ways.
 */
class FoiaSubmissionPrettyFormatter {

  /**
   * The entity ID of the FOIA request.
   *
   * @var int
   */
  protected $foiaRequestId;

  /**
   * The webform submission entity.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * An array of submission data.
   *
   * @var array
   */
  protected $submissionContents;

  /**
   * Constructor for FoiaSubmissionPrettyFormatter.
   *
   * @param int $foiaRequestId
   *   The ID of the FOIA Request entity.
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission entity.
   */
  public function __construct($foiaRequestId, WebformSubmissionInterface $webformSubmission) {
    $this->foiaRequestId = $foiaRequestId;
    $this->webformSubmission = $webformSubmission;
    $this->setSubmissionContents();
  }

  /**
   * Set the internal $submissionContents property.
   */
  protected function setSubmissionContents() {
    $submissionContents = $this->webformSubmission->getData();
    $this->listFileAttachmentNamesInSubmission($submissionContents);
    $submissionMetadata = [
      'request_id' => $this->foiaRequestId,
      'confirmation_id' => $this->webformSubmission->id(),
    ];
    $submissionContents = array_merge($submissionMetadata, $submissionContents);
    $this->submissionContents = $submissionContents;
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
    $webform = $this->webformSubmission->getWebform();
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
      $fids = $submissionContents[$fileAttachmentElementKey] ?? '';
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
   * @return string
   *   Returns the submission contents as a human-readable list.
   */
  public function formatSubmissionContentsAsList() {
    // Associative array of sections to groups of fields.
    $keys_by_section = [
      'Contact information' => [
        'name_first' => 'First name',
        'name_last' => 'Last name',
        'address_line1' => 'Mailing Address',
        'address_line2' => '',
        'address_city' => 'City',
        'address_state_province' => 'State/Province',
        'address_zip_postal_code' => 'Postal Code',
        'address_country' => 'Country',
        'phone_number' => 'Phone',
        'fax_number' => 'Fax',
        'company_organization' => 'Company/Organization',
        'email' => 'Email',
      ],
      'Request' => [
        'request_id' => 'Request ID',
        'confirmation_id' => 'Confirmation ID',
        'request_description' => 'Request description',
      ],
      'Supporting documentation' => [
        'attachments_supporting_documentation' => 'Additional Information',
      ],
      'Fees' => [
        'request_category' => 'Request category ID',
        'fee_waiver' => 'Fee waiver',
        'fee_waiver_explanation' => 'Explanation',
        'fee_amount_willing' => 'Willing to pay',
      ],
      'Expedited processing' => [
        'expedited_processing' => 'Expedited Processing',
        'expedited_processing_explanation' => 'Explanation',
      ],
    ];

    // Variable to keep track of which fields we've displayed.
    $keys_displayed = [];

    // Setup a timestamp variable to use below.
    $timestamp = new \DateTime('now', new \DateTimezone('US/Eastern'));

    // Display a message with a timestamp announcing the FOIA Request contents.
    $output = '<p>The following list contains the entire submission submitted ' . $timestamp->format('F d, Y h:i:sa') . ' ET, and is formatted for ease of viewing and printing.</p>';

    // First output all the hardcoded sections.
    foreach ($keys_by_section as $section => $keys) {
      $output .= '<hr><h3>' . $section . '</h3>';
      $rows = [];
      foreach ($keys as $key => $label) {
        if (!empty($this->submissionContents[$key])) {
          $rows[] = [
            ['data' => ['#markup' => "<strong>$label</strong>"]],
            ['data' => $this->submissionContents[$key]],
          ];
          // Remember which keys we displayed.
          $keys_displayed[] = $key;
        }
      }
      $table = [
        '#theme' => 'table',
        '#rows' => $rows,
        '#attributes' => ['width' => '500', 'font-family' => 'Helvetica'],
      ];
      $output .= \Drupal::service('renderer')->renderPlain($table);
    }

    // Next output the remaining fields.
    $rows = [];
    foreach ($this->submissionContents as $key => $value) {
      if (!in_array($key, $keys_displayed)) {
        $rows[] = [
          ['data' => ['#markup' => "<strong>$key</strong>"]],
          ['data' => $value],
        ];
      }
    }
    // Only output if there are actually additional fields.
    if (!empty($rows)) {
      $output .= '<h2>Additional information</h2>';
      $table = [
        '#theme' => 'table',
        '#rows' => $rows,
        '#attributes' => ['width' => '500', 'font-family' => 'Helvetica'],
      ];
      $output .= \Drupal::service('renderer')->renderPlain($table);
    }

    return $output;
  }

  /**
   * Formats the webform submission contents as an HTML table.
   *
   * @return string
   *   Returns the submission contents as an HTML table.
   */
  public function formatSubmissionContentsAsTable() {
    $table = [
      '#markup' => t('The following table contains the entire submission, and is formatted for ease of copy/pasting into a spreadsheet.') . '<br /><br />',
    ];

    $table['values'] = [
      '#theme' => 'table',
      '#header' => array_keys($this->submissionContents),
      '#rows' => ['data' => $this->submissionContents],
    ];

    return \Drupal::service('renderer')->renderPlain($table);
  }

  /**
   * Create a PDF version of the list output.
   */
  public function formatSubmissionContentsAsPdf() {
    $style = '<style>table{width:100%;table-layout: fixed;overflow-wrap: break-word;}</style>';
    $header = '<div>FOIA Request ' . $this->foiaRequestId . '</div><br /><br />';
    $dompdf = new Dompdf();
    $dompdf->loadHtml($style . $header . $this->formatSubmissionContentsAsList());
    $dompdf->setPaper('letter', 'portrait');
    $dompdf->render();
    return $dompdf->output();
  }

}
