<?php

namespace Drupal\foia_webform;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\UrlHelper;
use Drupal\file\Entity\File;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
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
  public function sendRequestToComponent(FoiaRequestInterface $foiaRequest, NodeInterface $agencyComponent) {
    $this->agencyComponent = $agencyComponent;
    $componentEndpoint = $this->agencyComponent->get('field_submission_api')->uri;

    if (!$componentEndpoint) {
      $error['message'] = 'Missing API Submission URL for component.';
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }

    // Force HTTPS.
    $scheme = parse_url($componentEndpoint, PHP_URL_SCHEME);
    if ($scheme != 'https') {
      $error['message'] = 'API URL for the component must use the HTTPS protocol.';
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }

    if (!UrlHelper::isValid($componentEndpoint, TRUE)) {
      $error['message'] = 'Invalid API URL for the component.';
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }

    $valuesToSubmit = $this->assembleRequestData($foiaRequest);
    return $this->submitToComponentEndpoint($componentEndpoint, $valuesToSubmit);
  }

  /**
   * Gathers the required fields for the API request.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request being submitted.
   *
   * @return array
   *   Return the assembled request data in an array.
   */
  protected function assembleRequestData(FoiaRequestInterface $foiaRequest) {
    // Get the webform submission values.
    $formValues = $this->getSubmissionValues($foiaRequest);

    // Get the agency information.
    $agencyInfo = $this->getAgencyInfo();

    $apiVersion = ['version' => FoiaSubmissionServiceInterface::VERSION];
    $foiaRequestId = ['request_id' => $foiaRequest->id()];
    $submissionValues = array_merge($foiaRequestId, $apiVersion, $agencyInfo, $formValues);

    return $submissionValues;
  }

  /**
   * Return the form submission values as an array.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request being submitted.
   *
   * @return array
   *   Returns the submission values as an array.
   */
  protected function getSubmissionValues(FoiaRequestInterface $foiaRequest) {
    $webformSubmission = WebformSubmission::load($foiaRequest->get('field_webform_submission_id')->value);
    $webform = $webformSubmission->getWebform();

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
   * Submit the FOIA request form values to the component endpoint.
   *
   * @param string $componentEndpoint
   *   The URL of the component's endpoint.
   * @param array $submissionValues
   *   An array containing the values to submit to the component endpoint.
   */
  protected function submitToComponentEndpoint($componentEndpoint, array $submissionValues) {
    $secretToken = $this->agencyComponent->get('field_submission_api_secret')->value;
    try {
      $response = $this->postToEndpoint($componentEndpoint, $submissionValues, $secretToken);
      return $this->parseAgencyResponse($response);
    }
    catch (RequestException $e) {
      if ($e->hasResponse()) {
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
          $this->log('error', "{$httpCodeMessagePrefix} {$error['message']}", $context);
          return FALSE;
        }
        if (isset($responseBody['code'])) {
          $this->handleErrorResponseFromComponent($responseBody, $responseCode);
          return FALSE;
        }
        $error = [
          'http_code' => $responseCode,
          'message' => 'Unexpected error response format from component',
        ];
        $this->addSubmissionError($error);
        $this->log('error', "{$httpCodeMessagePrefix} {$error['message']}", $context);
      }
      else {
        $exceptionCode = $e->getCode();
        $exceptionMessage = $e->getMessage();
        $error = [
          'message' => "Exception message: {$exceptionMessage}.",
        ];
        $context = [
          '@exception_code' => $exceptionCode,
        ];
        $exceptionCodeMessagePrefix = 'Exception code: @exception_code.';
        $this->addSubmissionError($error);
        $this->log('error', "{$exceptionCodeMessagePrefix} {$error['message']}", $context);
      }
      return FALSE;
    }
    catch (\Exception $e) {
      $exceptionCode = $e->getCode();
      $exceptionMessage = $e->getMessage();
      $error = [
        'message' => "Exception code: {$exceptionCode}. Exception message: {$exceptionMessage}",
      ];
      $this->addSubmissionError($error);
      $this->log('error', "{$error['message']}");
      return FALSE;
    }
  }

  /**
   * Submits a POST request to the agency component's endpoint.
   *
   * @param string $endpointUrl
   *   The URL of the component's endpoint.
   * @param array $submissionValues
   *   An array containing the values to submit to the component endpoint.
   * @param string $secretToken
   *   The secret token to use in the FOIA-API-SECRET header.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The Guzzle response.
   *
   * @throws \GuzzleHttp\Exception\TransferException
   *
   * @see http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions
   */
  protected function postToEndpoint($endpointUrl, array $submissionValues, $secretToken = '') {
    if ($secretToken) {
      return $this->httpClient->post($endpointUrl, [
        'json' => $submissionValues,
        'headers' => [
          'FOIA-API-SECRET' => $secretToken,
        ],
      ]);
    }
    else {
      return $this->httpClient->post($endpointUrl, [
        'json' => $submissionValues,
      ]);
    }
  }

  /**
   * Parses the response received from the agency component endpoint.
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
    $statusTrackingNumber = isset($responseBody['status_tracking_number']) ? $responseBody['status_tracking_number'] : '';
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
      'response_code' => $responseCode,
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
        if (isset($submissionValues[$fileAttachmentElementName])) {
          $attachments = $this->getAttachmentData($submissionValues[$fileAttachmentElementName]);
          unset($submissionValues[$fileAttachmentElementName]);
          $submissionValues[$fileAttachmentElementName] = $attachments;
        }
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
    $context['@agency_component_id'] = $this->agencyComponent->id();
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
    $messageToLog = "HTTP Code: @http_code. Error Code: @code. Error Message: @message. Error Description: @description.";
    $this->log('error', $messageToLog, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionErrors() {
    $submissionErrors = [];
    if ($this->errors) {
      $submissionErrors = $this->errors;
      $submissionErrors['type'] = 'api';
    }
    return $submissionErrors;
  }

  /**
   * Adds a submission error for later retrieval.
   *
   * @param array $error
   *   An associative array containing error information.
   */
  protected function addSubmissionError(array $error) {
    $this->errors['response_code'] = isset($error['http_code']) ? $error['http_code'] : '';;
    $this->errors['code'] = isset($error['code']) ? $error['code'] : '';
    $this->errors['message'] = isset($error['message']) ? $error['message'] : '';
    $this->errors['description'] = isset($error['description']) ? $error['description'] : '';
  }

}
