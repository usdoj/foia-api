<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_webform\FoiaSubmissionServiceApi;
use Drupal\foia_webform\FoiaSubmissionServiceInterface;
use Drupal\webform\WebformInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Drupal\foia_webform\AgencyLookupService;
use Drupal\file\Entity\File;

/**
 * Class FoiaSubmissionServiceApiTest.
 *
 * Tests the FoiaSubmissionServiceApi.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
class FoiaSubmissionServiceApiTest extends FoiaWebformKernelTestBase {

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Agency lookup service.
   *
   * @var \Drupal\foia_webform\AgencyLookupServiceInterface
   */
  protected $agencyLookupService;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * Submission Service Api.
   *
   * @var \Drupal\foia_webform\FoiaSubmissionServiceApi
   */
  protected $submissionServiceApi;

  /**
   * A FOIA Request.
   *
   * @var \Drupal\foia_request\Entity\FoiaRequestInterface
   */
  protected $foiaRequest;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('file');
    $this->setupAgencyLookupServiceMock();
    $this->setupFoiaRequest();
    $this->setupLoggerMock();
    $this->setupWebformSubmission();
  }

  /**
   * Tests receiving an error response from an agency component.
   */
  public function testErrorResponseFromComponent() {
    $this->foiaRequest->set('field_webform_submission_id', $this->webformSubmission->id());
    $responseContents = [
      'code' => 'A234',
      'message' => 'agency component not found',
      'description' => 'description of the error that is specific to the case management system',
    ];
    $this->setupHttpClientRequestExceptionMock($responseContents, 404);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $validSubmission = $this->submissionServiceApi->sendRequestToComponent($this->foiaRequest, $this->agencyComponent);
    $errorMessage = $this->submissionServiceApi->getSubmissionErrors();
    $this->assertEquals(FALSE, $validSubmission);
    $this->assertEquals(404, $errorMessage['response_code']);
    $this->assertEquals($responseContents['message'], $errorMessage['message']);
    $this->assertEquals($responseContents['description'], $errorMessage['description']);
  }

  /**
   * Tests generic exceptions that are thrown.
   */
  public function testExceptionPostingToComponent() {
    $this->foiaRequest->set('field_webform_submission_id', $this->webformSubmission->id());
    $exceptionMessage = 'A generic exception message.';
    $this->setupHttpClientExceptionMock($exceptionMessage);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $validSubmission = $this->submissionServiceApi->sendRequestToComponent($this->foiaRequest, $this->agencyComponent);
    $error = $this->submissionServiceApi->getSubmissionErrors();
    $this->assertEquals(FALSE, $validSubmission);
    $this->assertEquals("Exception code: 0. Exception message: {$exceptionMessage}", $error['message']);
  }

  /**
   * Tests the assembly of request data.
   */
  public function testAssesmbleRequestData() {
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $this->deleteWebformHandlers($this->webform);
    $webformSubmissionData = [
      'name_first' => 'Another',
      'name_last' => 'Test',
      'email' => 'test@test.com',
      'request_description' => 'The best request',
      'request_fee_waiver' => 'yes',
      'request_expedited_processing' => 'no',
    ];
    $this->setupWebformSubmission(NULL, $webformSubmissionData);
    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery();
    $query->condition('webform_id', $this->webform->id());
    foreach (\Drupal::entityTypeManager()->getStorage('webform_submission')->loadMultiple($query->execute()) as $submission) {
      $webformSubmission = $submission;
    }
    $apiVersion = ['version' => FoiaSubmissionServiceInterface::VERSION];
    $requestId = ['request_id' => $this->foiaRequest->id()];
    $agencyInfo = [
      'agency' => $this->agency->label(),
      'agency_component_name' => $this->agencyComponent->label(),
    ];
    $expectedData = array_merge($apiVersion, $requestId, $agencyInfo, $webformSubmissionData);

    $this->setProtectedProperty($this->submissionServiceApi, 'agencyComponent', $this->agencyComponent);
    $this->foiaRequest->set('field_webform_submission_id', $webformSubmission->id());
    $assembledData = $this->invokeMethod($this->submissionServiceApi, 'assembleRequestData', [$this->foiaRequest]);
    $this->assertEquals($expectedData, $assembledData);
  }

  /**
   * Tests the assembly of request data with attachments.
   */
  public function testAssesmbleRequestDataWithAttachments() {
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);

    $config = \Drupal::config('webform_template.settings')->get('webform_template_elements');
    $templateElements = yaml_parse($config);
    $this->webform->setElements($templateElements);
    $this->webform->save();
    $this->deleteWebformHandlers($this->webform);

    // Need to create Drupal file entity.
    $file = File::create([
      'uid' => 1,
      'filename' => 'test.txt',
      'uri' => 'public://test.txt',
      'status' => 1,
    ]);
    $file->save();

    $dir = dirname($file->getFileUri());
    if (!file_exists($dir)) {
      mkdir($dir, 0770, TRUE);
    }
    file_put_contents($file->getFileUri(), "test");
    $file->save();

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $file_usage->add($file, 'foia_webform', 'user', 1);
    $file->save();

    $webformSubmissionData = [
      'name_first' => 'Another',
      'name_last' => 'Test',
      'email' => 'test@test.com',
      'request_description' => 'The best request',
      'request_fee_waiver' => 'no',
      'request_expedited_processing' => 'no',
      'attachments_supporting_documentation' => [$file->id()],
    ];

    $webformSubmission = $this->setupWebformSubmission(NULL, $webformSubmissionData);

    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery();
    $query->condition('webform_id', $this->webform->id());
    foreach (\Drupal::entityTypeManager()->getStorage('webform_submission')->loadMultiple($query->execute()) as $submission) {
      $webformSubmission = $submission;
    }

    $webformSubmissionWithFileContents = [
      'name_first' => 'Another',
      'name_last' => 'Test',
      'email' => 'test@test.com',
      'request_description' => 'The best request',
      'request_fee_waiver' => 'no',
      'request_expedited_processing' => 'no',
      'attachments_supporting_documentation' => [
        [
          'content_type' => $file->getMimeType(),
          'filedata' => base64_encode(file_get_contents($file->getFileUri())),
          'filename' => $file->getFilename(),
          'filesize' => $file->getSize(),
        ],
      ],
    ];
    $apiVersion = ['version' => FoiaSubmissionServiceInterface::VERSION];
    $requestId = ['request_id' => $this->foiaRequest->id()];
    $agencyInfo = [
      'agency' => $this->agency->label(),
      'agency_component_name' => $this->agencyComponent->label(),
    ];
    $expectedData = array_merge($apiVersion, $requestId, $agencyInfo, $webformSubmissionWithFileContents);

    $this->setProtectedProperty($this->submissionServiceApi, 'agencyComponent', $this->agencyComponent);
    $this->foiaRequest->set('field_webform_submission_id', $webformSubmission->id());
    $assembledData = $this->invokeMethod($this->submissionServiceApi, 'assembleRequestData', [$this->foiaRequest]);
    $this->assertEquals($expectedData, $assembledData);
  }

  /**
   * Tests receiving a successful response from an agency component.
   */
  public function testSuccessResponseFromComponent() {
    $this->foiaRequest->set('field_webform_submission_id', $this->webformSubmission->id());
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $validSubmission = $this->submissionServiceApi->sendRequestToComponent($this->foiaRequest, $this->agencyComponent);
    $submissionError = $this->submissionServiceApi->getSubmissionErrors();
    $this->assertNotEquals(FALSE, $validSubmission);
    $this->assertEquals(200, $validSubmission['response_code']);
    $this->assertEquals($responseContents['id'], $validSubmission['id']);
    $this->assertEquals($responseContents['status_tracking_number'], $validSubmission['status_tracking_number']);
    $this->assertEquals('api', $validSubmission['type']);
    $this->assertEquals([], $submissionError);
  }

  /**
   * Sets up Guzzle error mock.
   */
  protected function setupHttpClientRequestExceptionMock(array $responseContents, $responseStatusCode) {
    $testAgencyErrorResponse = Json::encode($responseContents);
    $guzzleMock = new MockHandler([
      new RequestException("Error communicating with component", new Request('POST', 'test'), new Response($responseStatusCode, [], $testAgencyErrorResponse)),
    ]);

    $guzzleHandlerMock = HandlerStack::create($guzzleMock);
    $this->httpClient = new Client(['handler' => $guzzleHandlerMock]);
  }

  /**
   * Sets up Guzzle exception mock.
   */
  protected function setupHttpClientExceptionMock($exceptionMessage) {
    $guzzleMock = new MockHandler([
      new \Exception($exceptionMessage),
    ]);

    $guzzleHandlerMock = HandlerStack::create($guzzleMock);
    $this->httpClient = new Client(['handler' => $guzzleHandlerMock]);
  }

  /**
   * Sets up Guzzle success mock.
   */
  protected function setupHttpClientMock(array $responseContents, $responseStatusCode) {
    $testAgencyResponse = Json::encode($responseContents);
    $guzzleMock = new MockHandler([
      new Response($responseStatusCode, [], $testAgencyResponse),
    ]);

    $guzzleHandlerMock = HandlerStack::create($guzzleMock);
    $this->httpClient = new Client(['handler' => $guzzleHandlerMock]);
  }

  /**
   * Sets up agency lookup service mock.
   */
  protected function setupAgencyLookupServiceMock() {
    $entityTypeManager = \Drupal::entityTypeManager();
    $this->agencyLookupService = new AgencyLookupService($entityTypeManager);
  }

  /**
   * Sets up a FOIA request for testing.
   */
  protected function setupFoiaRequest() {
    $fields = [
      'field_webform_submission_id',
      'field_agency_component',
      'field_case_management_id',
      'field_tracking_number',
      'field_submission_time',
      'field_submission_method',
      'field_response_code',
      'field_error_message',
      'field_error_code',
      'field_error_description',
      'field_requester_email',
    ];
    $this->installFieldsOnEntity($fields, 'foia_request', 'foia_request');
    $this->foiaRequest = FoiaRequest::create();
    $this->foiaRequest->save();
  }

  /**
   * Sets up logger mock.
   */
  protected function setupLoggerMock() {
    $this->logger = $this->getMock('\Psr\Log\LoggerInterface');
  }

  /**
   * Deletes webform handlers to disable queuing of FOIA requests.
   *
   * @param \Drupal\webform\WebformInterface &$webform
   *   The webform to remove handlers for.
   */
  protected function deleteWebformHandlers(WebformInterface &$webform) {
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      $webform->deleteWebformHandler($handler);
    }
    $webform->save();
  }

}
