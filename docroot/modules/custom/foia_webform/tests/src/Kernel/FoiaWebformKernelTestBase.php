<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\KernelTests\KernelTestBase;

use Drupal\Component\Serialization\Json;
use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_webform\FoiaSubmissionServiceApi;
use Drupal\foia_webform\FoiaSubmissionServiceInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\webform\WebformInterface;
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
 * Base class for foia webform tests.
 */
abstract class FoiaWebformKernelTestBase extends KernelTestBase {

  use ReflectionTrait;
  use FieldInstallTrait;

  /**
   * Agency lookup service.
   *
   * @var \Drupal\foia_webform\AgencyLookupServiceInterface
   */
  protected $agencyLookupService;

  /**
   * A FOIA Request.
   *
   * @var \Drupal\foia_request\Entity\FoiaRequestInterface
   */
  protected $foiaRequest;

  /**
   * The foia submissions queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $foiaSubmissionsQueue;

  /**
   * Guzzle client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * Test webform to submit against.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'field_permissions',
    'file',
    'foia_request',
    'foia_webform',
    'link',
    'node',
    'options',
    'system',
    'taxonomy',
    'text',
    'user',
    'webform',
    'webform_template',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('webform', ['webform']);
    $this->installConfig(['webform', 'webform_template', 'foia_webform']);
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('foia_request');
    $this->installEntitySchema('webform_submission');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('file');
    $this->foiaSubmissionsQueue = \Drupal::service('queue')->get('foia_submissions');

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
      'field_requester_email',
    ];
    $this->installFieldsOnEntity($fields, 'foia_request', 'foia_request');
    $this->foiaRequest = FoiaRequest::create();
    $this->foiaRequest->save();
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
   * Sets up logger mock.
   */
  protected function setupLoggerMock() {
    $this->logger = $this->getMock('\Psr\Log\LoggerInterface');
  }

  /**
   * Sets up a webform submission.
   */
  protected function setupWebformSubmission() {
    $webformSubmission = WebformSubmission::create(['webform_id' => $this->webform->id(), 'data' => ['custom' => 'value']]);
    $webformSubmission->save();
    $this->webformSubmission = $webformSubmission;
  }

}
