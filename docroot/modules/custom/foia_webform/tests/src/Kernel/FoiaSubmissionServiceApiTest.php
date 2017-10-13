<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\foia_webform\FoiaSubmissionServiceApi;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeTypeInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Drupal\foia_webform\AgencyLookupService;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;

/**
 * Class FoiaSubmissionServiceApiTest.
 *
 * Tests the FoiaSubmissionServiceApi.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
class FoiaSubmissionServiceApiTest extends KernelTestBase {

  use ReflectionTrait;

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['webform', 'webform_template', 'foia_webform']);
    $this->installSchema('file', 'file_usage');
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

    // Check creating a submission with default data.
    $webformSubmission = WebformSubmission::create(['webform_id' => $this->webform->id(), 'data' => ['custom' => 'value']]);
    $webformSubmission->save();
    $this->webformSubmission = $webformSubmission;

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
    $this->setupLoggerMock();
  }

  /**
   * Tests receiving an error response from an agency component.
   */
  public function testErrorResponseFromComponent() {
    $responseContents = [
      'code' => 'A234',
      'message' => 'agency component not found',
      'description' => 'description of the error that is specific to the case management system',
    ];
    $this->setupHttpClientRequestExceptionMock($responseContents, 404);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $validSubmission = $this->submissionServiceApi->sendSubmissionToComponent($this->webformSubmission, $this->webform, $this->agencyComponent);
    $errorMessage = $this->submissionServiceApi->getSubmissionErrors();
    $this->assertEquals(FALSE, $validSubmission);
    $this->assertEquals(404, $errorMessage['http_code']);
    $this->assertEquals($responseContents['message'], $errorMessage['message']);
    $this->assertEquals($responseContents['description'], $errorMessage['description']);
  }

  /**
   * Tests generic exceptions that are thrown.
   */
  public function testExceptionPostingToComponent() {
    $exceptionMessage = 'A generic exception message.';
    $this->setupHttpClientExceptionMock($exceptionMessage);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $validSubmission = $this->submissionServiceApi->sendSubmissionToComponent($this->webformSubmission, $this->webform, $this->agencyComponent);
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
    $webformSubmissionData = [
      'first_name' => 'Another',
      'last_name' => 'Test',
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
    foreach (\Drupal::entityTypeManager()->getStorage('webform_submission')->loadMultiple($query->execute()) as $submission) {
      $webformSubmission = $submission;
    }
    $agencyInfo = [
      'agency' => $this->agency->label(),
      'agency_component_name' => $this->agencyComponent->label(),
    ];
    $expectedData = array_merge($webformSubmissionData, $agencyInfo);

    $this->setProtectedProperty($this->submissionServiceApi, 'agencyComponent', $this->agencyComponent);
    $assembledData = $this->invokeMethod($this->submissionServiceApi, 'assembleRequestData', [$webformSubmission, $webform]);
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
    $webform = Webform::create([
      'id' => 'a_test_webform',
    ]);

    $config = \Drupal::config('webform_template.settings')->get('webform_template_elements');
    $templateElements = yaml_parse($config);
    $webform->setElements($templateElements);
    $webform->save();

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
      'first_name' => 'Another',
      'last_name' => 'Test',
      'email' => 'test@test.com',
      'request_description' => 'The best request',
      'request_fee_waiver' => 'no',
      'request_expedited_processing' => 'no',
      'attachments_supporting_documentation' => [$file->id()],
    ];

    $webformSubmission = WebformSubmission::create([
      'webform_id' => $webform->id(),
      'data' => $webformSubmissionData,
    ]);
    $webformSubmission->save();

    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery();
    $query->condition('webform_id', $webform->id());
    foreach (\Drupal::entityTypeManager()->getStorage('webform_submission')->loadMultiple($query->execute()) as $submission) {
      $webformSubmission = $submission;
    }

    $webformSubmissionWithFileContents = [
      'first_name' => 'Another',
      'last_name' => 'Test',
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
    $agencyInfo = [
      'agency' => $this->agency->label(),
      'agency_component_name' => $this->agencyComponent->label(),
    ];
    $expectedData = array_merge($webformSubmissionWithFileContents, $agencyInfo);

    $this->setProtectedProperty($this->submissionServiceApi, 'agencyComponent', $this->agencyComponent);
    $assembledData = $this->invokeMethod($this->submissionServiceApi, 'assembleRequestData', [$webformSubmission, $webform]);
    $this->assertEquals($expectedData, $assembledData);
  }

  /**
   * Tests receiving a successful response from an agency component.
   */
  public function testSuccessResponseFromComponent() {
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $validSubmission = $this->submissionServiceApi->sendSubmissionToComponent($this->webformSubmission, $this->webform, $this->agencyComponent);
    $submissionError = $this->submissionServiceApi->getSubmissionErrors();
    $this->assertNotEquals(FALSE, $validSubmission);
    $this->assertEquals(200, $validSubmission['http_code']);
    $this->assertEquals($responseContents['id'], $validSubmission['id']);
    $this->assertEquals($responseContents['status_tracking_number'], $validSubmission['status_tracking_number']);
    $this->assertEquals('api', $validSubmission['type']);
    $this->assertEquals([], $submissionError);

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
    $this->addFieldsToComponentType($agencyComponentType);
    $this->createAgencyComponentNode();
  }

  /**
   * Adds fields to agency component content type.
   */
  protected function addFieldsToComponentType(NodeTypeInterface $agencyComponentType) {
    $this->addFieldToComponentType('field_request_submission_form');
    $this->addFieldToComponentType('field_submission_api');
    $this->addFieldToComponentType('field_agency');
  }

  /**
   * Adds field to agency component content type.
   */
  protected function addFieldToComponentType($fieldName) {
    $path = '/var/www/dojfoia/config/default';
    $yml = yaml_parse(file_get_contents($path . "/field.storage.node.{$fieldName}.yml"));
    FieldStorageConfig::create($yml)->save();
    $yml = yaml_parse(file_get_contents($path . "/field.field.node.agency_component.{$fieldName}.yml"));
    FieldConfig::create($yml)->save();
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
      'field_submission_api' => 'http://atest.com',
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
   * Sets up logger mock.
   */
  protected function setupLoggerMock() {
    $this->logger = $this->getMock('\Psr\Log\LoggerInterface');
  }

}
