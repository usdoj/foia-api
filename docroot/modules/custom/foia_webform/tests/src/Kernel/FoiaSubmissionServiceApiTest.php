<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\file_entity\Entity\FileType;
use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_webform\AgencyLookupService;
use Drupal\foia_webform\FoiaSubmissionServiceApi;
use Drupal\foia_webform\FoiaSubmissionServiceInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Class FoiaSubmissionServiceApiTest.
 *
 * Tests the FoiaSubmissionServiceApi.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
class FoiaSubmissionServiceApiTest extends KernelTestBase {

  use ReflectionTrait;
  use FieldInstallTrait;

  /**
   * Test agency.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $agency;

  /**
   * Test webform to submit against.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * Test webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

  /**
   * Test agency component we're submitting to.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $agencyComponent;

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
  public static $modules = [
    'webform_template',
    'webform',
    'system',
    'user',
    'foia_webform',
    'node',
    'field',
    'taxonomy',
    'field_permissions',
    'text',
    'file',
    'link',
    'foia_request',
    'options',
    'foia_file',
    'file_entity',
    'image',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('webform', ['webform']);
    $this->installConfig(['webform', 'webform_template', 'foia_webform']);
    $this->installSchema('file', ['file_usage']);
    $this->installEntitySchema('foia_request');
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');

    // Creates webform and specifies to use the template fields.
    $webformWithTemplate = Webform::create(['id' => 'webform_with_template']);
    $webformWithTemplate->set('foia_template', 1);
    $webformWithTemplate->save();
    $this->webform = $webformWithTemplate;

    Vocabulary::create([
      'name' => 'Agency',
      'vid' => 'agency',
    ])->save();
    Term::create([
      'name' => 'A Test Agency',
      'vid' => 'agency',
    ])->save();

    $agency = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => 'A Test Agency']);

    $this->agency = reset($agency);

    $this->setupAgencyComponent();
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
    $webform = Webform::create([
      'id' => $this->randomMachineName(),
    ]);
    $webform->set('foia_template', 1);
    $webform->save();
    $this->deleteWebformHandlers($webform);
    $webformSubmissionData = [
      'name_first' => 'Another',
      'name_last' => 'Test',
      'email' => 'test@test.com',
      'request_description' => 'The best request',
      'request_fee_waiver' => 'yes',
      'request_expedited_processing' => 'no',
    ];
    $webformSubmission = WebformSubmission::create([
      'webform_id' => $webform->id(),
      'data' => $webformSubmissionData,
    ]);
    $webformSubmission->save();
    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery();
    $query->condition('webform_id', $webform->id());
    $query->accessCheck();
    foreach (\Drupal::entityTypeManager()->getStorage('webform_submission')->loadMultiple($query->execute()) as $submission) {
      $webformSubmission = $submission;
    }
    $apiVersion = ['version' => FoiaSubmissionServiceInterface::VERSION];
    $requestId = ['request_id' => $this->foiaRequest->id()];
    $confirmationId = ['confirmation_id' => $webformSubmission->id()];
    $agencyInfo = [
      'agency' => $this->agency->label(),
      'agency_component_name' => $this->agencyComponent->label(),
    ];
    $expectedData = array_merge($apiVersion, $agencyInfo, $requestId, $confirmationId, $webformSubmissionData);

    $this->setProtectedProperty($this->submissionServiceApi, 'agencyComponent', $this->agencyComponent);
    $this->foiaRequest->set('field_webform_submission_id', $webformSubmission->id());
    $assembledData = $this->invokeMethod($this->submissionServiceApi, 'assembleRequestData', [$this->foiaRequest]);
    $this->assertEquals($expectedData, $assembledData);
  }

  /**
   * Tests the assembly of request data with attachments.
   */
  public function testAssesmbleRequestDataWithAttachments() {
    $configPath = '/var/www/dojfoia/config/default';
    $fileConfig = yaml_parse(file_get_contents($configPath . "/file_entity.type.attachment_support_document.yml"));
    FileType::create($fileConfig)->save();
    $this->installFieldOnEntity('field_virus_scan_status', 'file', 'attachment_support_document', $configPath);

    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $webform = Webform::create([
      'id' => 'a_test_webform',
    ]);

    $config = \Drupal::config('webform_template.settings')->get('webform_template_elements');
    $templateElements = yaml_parse($config);
    $webform->setElements($templateElements);
    $webform->save();
    $this->deleteWebformHandlers($webform);

    $files = $this->createFiles();
    $fids = array_keys($files);
    foreach ($files as $file) {
      if ($file->get('field_virus_scan_status')->value == 'clean') {
        $cleanFile = $file;
      }
      else {
        $virusFile = $file;
      }
    }

    $webformSubmissionData = [
      'name_first' => 'Another',
      'name_last' => 'Test',
      'email' => 'test@test.com',
      'request_description' => 'The best request',
      'request_fee_waiver' => 'no',
      'request_expedited_processing' => 'no',
      'attachments_supporting_documentation' => $fids,
    ];

    $webformSubmission = WebformSubmission::create([
      'webform_id' => $webform->id(),
      'data' => $webformSubmissionData,
    ]);
    $webformSubmission->save();

    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery();
    $query->condition('webform_id', $webform->id());
    $query->accessCheck();
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
          'content_type' => $cleanFile->getMimeType(),
          'filedata' => base64_encode(file_get_contents($cleanFile->getFileUri())),
          'filename' => $cleanFile->getFilename(),
          'filesize' => $cleanFile->getSize(),
        ],
      ],
      'removed_files' => [
        $virusFile->getFilename(),
      ],
    ];
    $apiVersion = ['version' => FoiaSubmissionServiceInterface::VERSION];
    $requestId = ['request_id' => $this->foiaRequest->id()];
    $confirmationId = ['confirmation_id' => $webformSubmission->id()];
    $agencyInfo = [
      'agency' => $this->agency->label(),
      'agency_component_name' => $this->agencyComponent->label(),
    ];
    $expectedData = array_merge($apiVersion, $agencyInfo, $requestId, $confirmationId, $webformSubmissionWithFileContents);

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
   * Creates test attachments.
   */
  protected function createFiles() {
    $filesToCreate = [
      'test1.txt' => 'clean',
      'test2.txt' => 'virus',
    ];
    $fileEntities = [];

    foreach ($filesToCreate as $fileName => $fileVirusStatus) {
      // Create Drupal file entity.
      $file = FileEntity::create([
        'type' => 'attachment_support_document',
        'uid' => 1,
        'filename' => $fileName,
        'uri' => "public://{$fileName}",
        'status' => 1,
      ]);
      $file->save();

      $dir = dirname($file->getFileUri());
      if (!file_exists($dir)) {
        mkdir($dir, 0770, TRUE);
      }
      file_put_contents($file->getFileUri(), "test");
      $file->set('field_virus_scan_status', $fileVirusStatus);
      $file->save();

      /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($file, 'foia_webform', 'user', 1);
      $file->save();
      $fileEntities[$file->id()] = $file;
    }
    return $fileEntities;

  }

  /**
   * Adds agency component content type.
   */
  protected function setupAgencyComponent() {
    $agencyComponentTypeDefinition = [
      'type' => 'agency_component',
      'name' => t('Agency Component'),
      'description' => 'An agency component to which a request can be sent and which will be fulfilling requests.',
    ];
    $agencyComponentType = NodeType::create($agencyComponentTypeDefinition);
    $agencyComponentType->save();
    $fieldsToSetup = [
      'field_request_submission_form',
      'field_submission_api',
      'field_submission_api_secret',
      'field_agency',
    ];
    $this->installFieldsOnEntity($fieldsToSetup, 'node', 'agency_component');
    $this->createAgencyComponentNode();
  }

  /**
   * Creates an agency component entity.
   */
  protected function createAgencyComponentNode() {
    /** @var \Drupal\node\NodeInterface $agencyComponent */
    $agencyComponent = Node::create([
      'type' => 'agency_component',
      'title' => t('A Test Agency Component'),
      'field_portal_submission_format' => 'api',
      'field_submission_api' => [
        'uri' => 'https://atest.com',
      ],
      'field_submission_api_secret' => 'secret_token',
      'field_request_submission_form' => [
        'target_id' => $this->webform->id(),
      ],
      'field_agency' => [
        'target_id' => $this->agency->id(),
      ],
    ]);
    $agencyComponent->save();
    $this->agencyComponent = $agencyComponent;
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
    ];
    $this->installFieldsOnEntity($fields, 'foia_request', 'foia_request');
    $this->foiaRequest = FoiaRequest::create();
    $this->foiaRequest->save();
  }

  /**
   * Sets up logger mock.
   */
  protected function setupLoggerMock() {
    $this->logger = $this->createMock('\Psr\Log\LoggerInterface');
  }

  /**
   * Sets up a webform submission.
   */
  protected function setupWebformSubmission() {
    $webformSubmission = WebformSubmission::create(
      ['webform_id' => $this->webform->id(), 'data' => ['custom' => 'value']]
    );
    $webformSubmission->save();
    $this->webformSubmission = $webformSubmission;
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
