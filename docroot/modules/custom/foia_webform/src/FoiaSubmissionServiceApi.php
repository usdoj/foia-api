<?php

namespace Drupal\foia_webform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Class FoiaSubmissionServiceApi.
 *
 * @package Drupal\foia_webform\Plugin\QueueWorker
 */
class FoiaSubmissionServiceApi implements FoiaSubmissionServiceInterface {

  /**
   * The Agency Lookup service.
   *
   * @var \Drupal\foia_webform\AgencyLookupService
   */
  protected $agencyLookUpService;

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
    $this->agencyLookUpService = $agencyLookupService;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ConfigFactoryInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('http_client'),
      $container->get('foia_webform.agency_lookup_service'),
      $container->get('logger.channel.foia_webform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function sendSubmissionToComponent(WebformSubmissionInterface $webformSubmission, NodeInterface $agencyComponent) {
    $apiUrl = $agencyComponent->get('field_submission_api');

    if ($apiUrl) {
      $requestData = $this->assembleRequestData($webformSubmission, $agencyComponent);
    }
    else {
      $this->logger
        ->error('Unable to submit request via the API. Missing API Submission URL for node %component',
          [
            '%component' => $agencyComponent->id(),
            'link' => $agencyComponent->toLink($this->t('Edit Component'), 'edit-form')->toString(),
          ]
        );
    }
  }

  /**
   * Gathers the required fields for the API request.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The FOIA form submission form values.
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The Agency Component node object.
   *
   * @return array
   *   Return the assemble request data in an array.
   */
  public function assembleRequestData(WebformSubmissionInterface $webformSubmission, NodeInterface $agencyComponent) {
    $submissionValues = $this->getSubmissionValues($webformSubmission);
    $agencyInfo = $this->getAgencyInfo($agencyComponent);
    $requestValues = array_merge($submissionValues, $agencyInfo);

    return $requestValues;
  }

  /**
   * Return the FOIA form submission values as an array.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The Webform submission values.
   *
   * @return array
   *   Returns the submission values as an array.
   */
  public function getSubmissionValues(WebformSubmissionInterface $webformSubmission) {
    return $webformSubmission->getData();
  }

  /**
   * Parse the Agency information from the Agency Component.
   *
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The Agency Component node object.
   */
  public function getAgencyInfo(NodeInterface $agencyComponent) {
    return [
      'agency' => $this->agencyLookUpService->getAgencyFromComponent($agencyComponent),
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
    if (!empty($files)) {
      $fileData = [];
      foreach ($files as $fid) {
        $currentFile = File::load($fid);
        print_r($currentFile);
        $base64 = base64_encode(file_get_contents($currentFile->getFileUri()));
        $fileData[] = [
          'content_type' => $currentFile->getMimeType(),
          'filedata' => $base64,
          'filename' => $currentFile->getFilename(),
          'filesize' => $currentFile->getSize(),
        ];
      }
    }
  }

}
