<?php

namespace Drupal\foia_webform;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Class FoiaSubmissionServiceApi.
 */
class FoiaSubmissionServiceApi implements FoiaSubmissionServiceInterface {

  /**
   * The Agency Lookup service.
   *
   * @var \Drupal\foia_webform\AgencyLookupService
   */
  protected $agencyLookupService;

  /**
   * An HTTP client to post FOIA requests to an Agency Component API endpoint.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * FoiaSubmissionServiceApi constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP Guzzle client.
   * @param \Drupal\foia_webform\AgencyLookupServiceInterface $agencyLookupService
   *   The Agency Lookup service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(ClientInterface $httpClient, AgencyLookupServiceInterface $agencyLookupService, LoggerInterface $logger) {
    $this->httpClient = $httpClient;
    $this->agencyLookupService = $agencyLookupService;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function sendSubmissionToComponent(WebformSubmissionInterface $webformSubmission, WebformInterface $webform, NodeInterface $agencyComponent) {
    $submissionResponse = FALSE;
    $apiUrl = $agencyComponent->get('field_submission_api')->value;

    if (!UrlHelper::isValid($apiUrl, TRUE)) {
      $apiUrl = FALSE;
      $this->logger
        ->error('Invalid API URL for the component %nid',
          ['%nid' => $agencyComponent->id()]
        );
    }
    $valuesToSubmit = NULL;

    if ($apiUrl) {
      $valuesToSubmit = $this->assembleRequestData($webformSubmission, $agencyComponent, $webform);
    }
    else {
      $this->logger
        ->error('Unable to submit request via the API. Missing API Submission URL for node %component',
          [
            '%component' => $agencyComponent->id(),
            'link' => $agencyComponent->toLink(t('Edit Component'), 'edit-form')->toString(),
          ]
        );
    }

    if ($valuesToSubmit && $apiUrl) {
      $submissionResponse = $this->submitToApi($apiUrl, $valuesToSubmit);
    }

    return $submissionResponse;
  }

  /**
   * Gathers the required fields for the API request.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The FOIA form submission form values.
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The Agency Component node object.
   * @param \Drupal\webform\WebformInterface $webform
   *   The Webform node object.
   *
   * @return array
   *   Return the assemble request data in an array.
   */
  public function assembleRequestData(WebformSubmissionInterface $webformSubmission, NodeInterface $agencyComponent, WebformInterface $webform) {
    // Get the webform submission values.
    $formValues = $this->getSubmissionValues($webformSubmission, $webform);

    // Get the agency information.
    $agencyInfo = $this->getAgencyInfo($agencyComponent);

    $submissionValues = array_merge($formValues, $agencyInfo);

    return $submissionValues;
  }

  /**
   * Return the FOIA form submission values as an array.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The Webform submission values.
   * @param \Drupal\webform\WebformInterface $webform
   *   The Webform node object.
   *
   * @return array
   *   Returns the submission values as an array.
   */
  public function getSubmissionValues(WebformSubmissionInterface $webformSubmission, WebformInterface $webform) {
    $submissionValues = $webformSubmission->getData();
    // If there are files attached, load the files and add the file metadata.
    if ($webform->hasManagedFile()) {
      $attachments = [];
      $fileKeys = $this->getFileAttachmentElementsOnWebform($webform);
      if ($fileKeys) {
        foreach ($fileKeys as $key) {
          $attachments[$key] = $this->getAttachmentData($submissionValues[$key]);
          unset($submissionValues[$key]);
        }
      }
      if (!empty($attachments)) {
        $submissionValues['attachments'] = $attachments;
      }
    }

    return $submissionValues;
  }

  /**
   * Fetch the Agency information from the Agency Component.
   *
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The Agency Component node object.
   */
  public function getAgencyInfo(NodeInterface $agencyComponent) {
    $agencyTerm = $this->agencyLookupService->getAgencyFromComponent($agencyComponent);

    return [
      'agency' => ($agencyTerm) ? $agencyTerm->label() : '',
      'agency_component_name' => $agencyComponent->label(),
    ];
  }

  /**
   * Get the metadata for the file attachments.
   *
   * @param array $files
   *   An array containing the file IDs for the file attachments.
   */
  public function getAttachmentData(array $files) {
    $fileContents = [];
    if (!empty($files)) {
      foreach ($files as $fid) {
        $currentFile = File::load($fid);
        $base64 = base64_encode(file_get_contents($currentFile->getFileUri()));
        $fileContents[] = [
          'content_type' => $currentFile->getMimeType(),
          'filedata' => $base64,
          'filename' => $currentFile->getFilename(),
          'filesize' => (int) $currentFile->getSize(),
        ];
      }
    }
    return $fileContents;
  }

  /**
   * Submit the FOIA request form values to the API.
   *
   * @param string $apiUrl
   *   The API URL.
   * @param array $submissionValues
   *   An array containing the values to submit to the API.
   */
  private function submitToApi($apiUrl, array $submissionValues) {
    $client = $this->httpClient;
    try {
      $requestResponse = $client->post($apiUrl, [
        'json' => $submissionValues,
      ]);
      $response = Json::decode($requestResponse);
      if (isset($response['id']) && isset($response['status_tracking_number'])) {
        $submissionResponse = [
          'id' => $response['id'],
          'status_tracking_number' => $response['status_tracking_number'],
        ];
      }
      return (isset($submissionResponse)) ? $submissionResponse : [];
    }
    catch (RequestException $e) {
      $rawResponse = $e->getResponse()->getBody();
      $response = Json::decode($rawResponse);
      if (empty($response)) {
        $loggerVariables = [
          '@exception_message' => $e->getMessage(),
        ];
        $this->logger->error('Send API error: Exception: @exception_message', $loggerVariables);
      }
      return FALSE;
    }
    catch (\Exception $e) {
      $loggerVariables = [
        '@exception_message' => $e->getMessage(),
      ];
      $this->logger->error('Send API error: Exception: @exception_message', $loggerVariables);
      return FALSE;
    }
    finally {
      $this->logOutgoingPost($submissionValues);
    }
  }

  /**
   * Helper function to log outgoing POST messages being sent.
   *
   * @param string $message
   *   The message to be logged.
   */
  public function logOutgoingPost($message) {
    $this->logger->debug("Sending outgoing POST (in JSON): @message", ['@message' => Json::encode($message)]);
  }

  /**
   * Gets the names of file attachment elements on the webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform being submitted against.
   *
   * @return array
   *   Returns an array of the names of the file attachment elements on the
   *   webform being submitted against.
   */
  protected function getFileAttachmentElementsOnWebform(WebformInterface $webform) {
    $elements = $webform->getElementsInitialized();
    $fileAttachmentElementKeys = [];
    foreach ($elements as $key => $element) {
      if (isset($element['#type']) && $element['#type'] == 'managed_file') {
        $fileAttachmentElementKeys[] = $key;
      }
    }
    return $fileAttachmentElementKeys;
  }

}
