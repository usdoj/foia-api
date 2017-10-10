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
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

/**
 * Class FoiaSubmissionServiceApi.
 */
class FoiaSubmissionServiceApi implements FoiaSubmissionServiceInterface {

  /**
   * The Agency Lookup service.
   *
   * @var \Drupal\foia_webform\AgencyLookupServiceInterface
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
   * The agency component node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $agencyComponent;

  /**
   * Submission-related error messages.
   *
   * @var array
   */
  protected $errors;

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
    $this->agencyComponent = $agencyComponent;
    $apiUrl = $this->agencyComponent->get('field_submission_api')->value;

    if (!UrlHelper::isValid($apiUrl, TRUE)) {
      $error['message'] = 'Invalid API URL for the component';
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }
    $valuesToSubmit = NULL;

    if ($apiUrl) {
      $valuesToSubmit = $this->assembleRequestData($webformSubmission, $webform);
      return $this->submitToApi($apiUrl, $valuesToSubmit);
    }
    else {
      $error['message'] = 'Missing API Submission URL for component.';
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }
  }

  /**
   * Gathers the required fields for the API request.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The FOIA form submission form values.
   * @param \Drupal\webform\WebformInterface $webform
   *   The Webform node object.
   *
   * @return array
   *   Return the assemble request data in an array.
   */
  protected function assembleRequestData(WebformSubmissionInterface $webformSubmission, WebformInterface $webform) {
    // Get the webform submission values.
    $formValues = $this->getSubmissionValues($webformSubmission, $webform);

    // Get the agency information.
    $agencyInfo = $this->getAgencyInfo();

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
  protected function getSubmissionValues(WebformSubmissionInterface $webformSubmission, WebformInterface $webform) {
    $submissionValues = $webformSubmission->getData();
    // If there are files attached, load the files and add the file metadata.
    if ($webform->hasManagedFile()) {
      $this->replaceFidsWithFileContents($webform, $submissionValues);
    }

    return $submissionValues;
  }

  /**
   * Fetch the Agency information from the Agency Component.
   *
   * @return array
   *   Returns an associative array containing the agency and component names.
   */
  protected function getAgencyInfo() {
    $agencyTerm = $this->agencyLookupService->getAgencyFromComponent($this->agencyComponent);

    return [
      'agency' => ($agencyTerm) ? $agencyTerm->label() : '',
      'agency_component_name' => $this->agencyComponent->label(),
    ];
  }

  /**
   * Submit the FOIA request form values to the API.
   *
   * @param string $apiUrl
   *   The API URL.
   * @param array $submissionValues
   *   An array containing the values to submit to the API.
   */
  protected function submitToApi($apiUrl, array $submissionValues) {
    try {
      /** @var \GuzzleHttp\Psr7\Response $response */
      $response = $this->httpClient->post($apiUrl, [
        'json' => $submissionValues,
      ]);
      return $this->parseAgencyResponse($response);
    }
    catch (RequestException $e) {
      $response = $e->getResponse();
      $responseCode = $response->getStatusCode();
      $responseBody = Json::decode($response->getBody());
      $context = [
        '@http_code' => $responseCode,
      ];
      $httpCodeMessagePrefix = 'HTTP Code: @http_code.';
      if (empty($responseBody)) {
        $error = [
          'http_code' => $responseCode,
          'message' => 'Did not receive JSON response from component.',
        ];
        $this->addSubmissionError($error);
        $this->log('error', "${httpCodeMessagePrefix} {$error['message']}", $context);
        return FALSE;
      }
      if (isset($responseBody['code'])) {
        $this->handleErrorResponseFromComponent($responseBody, $responseCode);
        return FALSE;
      }
      $error = [
        'http_code' => $responseCode,
        'message' => 'Unexpected error response format from component.',
      ];
      $this->addSubmissionError($error);
      $this->log('error', "${httpCodeMessagePrefix} {$error['message']}", $context);
      return FALSE;
    }
    catch (\Exception $e) {
      $response = $e->getResponse();
      $error = [
        'http_code' => $response->getStatusCode(),
        'message' => $e->getMessage(),
      ];
      $this->addSubmissionError($error);
      $context = [
        '@http_code' => $error['http_code'],
        '@exception_message' => $error['message'],
      ];
      $httpCodeMessagePrefix = 'HTTP Code: @http_code.';
      $this->log('error', "${httpCodeMessagePrefix} Exception: @exception_message", $context);
      return FALSE;
    }
  }

  /**
   * Parses the response received from the agency component endpiont.
   *
   * @param \GuzzleHttp\Psr7\Response $response
   *   The response object.
   *
   * @return array|mixed
   *   An associative array with a request id and tracking number, if available.
   *   Otherwise an empty string.
   */
  protected function parseAgencyResponse(Response $response) {
    $responseBody = Json::decode($response->getBody());
    $responseCode = $response->getStatusCode();
    // Not a json-formatted response like we expected.
    if (empty($responseBody)) {
      $error = [
        'http_code' => $responseCode,
        'message' => 'Did not receive JSON response from component.',
      ];
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }
    $id = isset($responseBody['id']) ? $responseBody['id'] : '';
    $statusTrackingNumber = isset($response['status_tracking_number']) ? $response['status_tracking_number'] : '';
    if (!$id) {
      $error = [
        'http_code' => $responseCode,
        'message' => 'Did not receive ID in response from component.',
      ];
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }
    $submissionResponse = [
      'type' => 'api',
      'http_code' => $responseCode,
      'id' => $id,
      'status_tracking_number' => $statusTrackingNumber,
    ];
    return $submissionResponse;
  }

  /**
   * Replaces Drupal fids with binary file attachment data.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform the submission belongs to.
   * @param array $submissionValues
   *   The values submitted.
   */
  protected function replaceFidsWithFileContents(WebformInterface $webform, array &$submissionValues) {
    $fileAttachmentElementNames = $this->getFileAttachmentElementsOnWebform($webform);
    if ($fileAttachmentElementNames) {
      foreach ($fileAttachmentElementNames as $fileAttachmentElementName) {
        $attachments = $this->getAttachmentData($submissionValues[$fileAttachmentElementName]);
        unset($submissionValues[$fileAttachmentElementName]);
        $submissionValues[$fileAttachmentElementName] = $attachments;
      }
    }
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

  /**
   * Get the file data for each file attachment.
   *
   * @param array $files
   *   An array containing the file IDs for the file attachments.
   *
   * @return array
   *   An array of file data for each file attachment.
   */
  protected function getAttachmentData(array $files) {
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
   * Helper function which logs with appropriate context.
   *
   * @param string $level
   *   The level with which to log (e.g. error, notice, etc.).
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The message context.
   * @param string $api
   *   The relevant API.
   */
  protected function log($level, $message, array $context = [], $api = 'Submission Service API') {
    $context['agency_component_id'] = $this->agencyComponent->id();
    $this->logger->log($level, "{$api}: Agency Component: @agency_component_id. {$message}", $context);
  }

  /**
   * Helper function to Log JSON error object received from Agency Component.
   *
   * @param array $response
   *   Error object received from Agency Component.
   * @param int $responseCode
   *   HTTP response code.
   */
  protected function handleErrorResponseFromComponent(array $response, $responseCode) {
    $error['http_code'] = $responseCode;
    $error['code'] = $response['code'];
    $error['message'] = isset($response['message']) ? $response['message'] : '';
    $error['description'] = isset($response['description']) ? $response['description'] : '';
    $this->addSubmissionError($error);
    $context = [
      '@http_code' => $error['http_code'],
      '@code' => $error['code'],
      '@message' => $error['message'],
      '@description' => $error['description'],
    ];
    $messageToLog = "HTTP Code: @http_code. Code: @code. Message: @message. Description: @description.";
    $this->log('error', $messageToLog, $context);
  }

  /**
   * Returns submission-related error messages.
   *
   * @return array
   *   Array of submission-related error messages.
   */
  public function getSubmissionErrors() {
    $submissionErrors = $this->errors;
    $submissionErrors['type'] = 'api';
    return $submissionErrors;
  }

  /**
   * Adds a submission error for later retrieval.
   *
   * @param array $error
   *   An associative array containing error information.
   */
  protected function addSubmissionError(array $error) {
    $this->errors['http_code'] = isset($error['http_code']) ? $error['http_code'] : '';;
    $this->errors['error_code'] = isset($error['error_code']) ? $error['error_code'] : '';
    $this->errors['message'] = isset($error['message']) ? $error['message'] : '';
    $this->errors['description'] = isset($error['description']) ? $error['description'] : '';
  }

}
