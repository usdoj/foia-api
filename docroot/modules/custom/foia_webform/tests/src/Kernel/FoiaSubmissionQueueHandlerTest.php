<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;

/**
 * Class FoiaSubmissionServiceApiTest.
 *
 * Tests the FoiaSubmissionServiceApi.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
class FoiaSubmissionServiceQueueHandlerTest extends KernelTestBase {

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
   * The foia submissions queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $foiaSubmissionsQueue;

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
    'file',
    'taxonomy',
    'field_permissions',
    'text',
    'link',
    'foia_request',
    'options',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('webform', ['webform']);
    $this->installConfig(['webform', 'webform_template', 'foia_webform']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('file', ['file_usage']);
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
    $this->setupFoiaRequestEntity();
  }

  /**
   * Tests that a FOIA request ID is queued when a webform is submitted.
   */
  public function testFoiaRequestCreatedAndQueuedOnWebformSubmission() {
    $this->setupWebformSubmission();

    $queuedSubmission = $this->foiaSubmissionsQueue->claimItem()->data;
    $this->assertNotEmpty($queuedSubmission, "Expected a FOIA request ID to be queued, but nothing was found in the queue.");
    $this->assertEquals('1', $queuedSubmission->id, "Queued FOIA Request ID does not match expected.");

    $foiaRequest = FoiaRequest::load($queuedSubmission->id);
    $this->assertEquals('Drupal\foia_request\Entity\FoiaRequest', get_class($foiaRequest));

    // Verifies FOIA Request setup with appropriate defaults.
    $this->assertEquals($this->agencyComponent->id(), $foiaRequest->get('field_agency_component')->target_id, 'Created FOIA Request with no or incorrect agency component.');
    $this->assertEquals($this->webformSubmission->id(), $foiaRequest->get('field_webform_submission_id')->value, 'Created FOIA Request with no or incorrect webform submission id.');
    $this->assertNull($foiaRequest->get('field_requester_email')->value, 'Created FOIA Request with requester email despite no email address being submitted.');
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus(), 'Created FOIA Request with incorrect status.');
    $this->assertNotEmpty($foiaRequest->getCreatedTime(), 'Created FOIA Request without a created timestamp.');
  }

  /**
   * Tests that a request with an attachment gets "Pending virus scan" status.
   */
  public function testFoiaRequestAttachmentPendingScan() {

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
      'request_description' => 'show me the info!',
      'attachments_supporting_documentation' => $file->id(),
    ];

    $webformSubmission = WebformSubmission::create([
      'webform_id' => $this->webform->id(),
      'data' => $webformSubmissionData,
    ]);
    $webformSubmission->save();
    $this->webformSubmission = $webformSubmission;

    $queuedSubmission = $this->foiaSubmissionsQueue->claimItem()->data;

    $this->assertNotEmpty($queuedSubmission, "Expeted a FOIA request ID to be queued, but nothing was found in the queue.");
    $this->assertEquals('1', $queuedSubmission->id, "Queued FOIA Request ID does not match expected.");

    $foiaRequest = FoiaRequest::load($queuedSubmission->id);
    $this->assertEquals(FoiaRequestInterface::STATUS_SCAN, $foiaRequest->getRequestStatus());
  }

  /**
   * Tests request not queued when a webform is not associated to a component.
   */
  public function testFoiaRequestIdNotQueuedOnWebformSubmission() {
    $this->agencyComponent->field_request_submission_form->target_id = '';
    $this->agencyComponent->save();
    $this->setupWebformSubmission();

    $queuedSubmission = $this->foiaSubmissionsQueue->claimItem();
    $this->assertEmpty($queuedSubmission, "Expected the queue to be empty, but was able to claim an item.");
  }

  /**
   * Tests that a queued FOIA request contains the requester email address.
   */
  public function testQueuedFoiaRequestContainsRequesterEmailAddress() {
    $testRequesterEmailAddress = 'requester@requester.com';
    $webformSubmission = WebformSubmission::create(['webform_id' => $this->webform->id(), 'data' => ['email' => $testRequesterEmailAddress]]);
    $webformSubmission->save();
    $this->webformSubmission = $webformSubmission;

    $queuedSubmission = $this->foiaSubmissionsQueue->claimItem()->data;
    $this->assertNotEmpty($queuedSubmission, "Expected a FOIA request ID to be queued, but nothing was found in the queue.");

    $foiaRequest = FoiaRequest::load($queuedSubmission->id);
    $requesterEmailAddress = $foiaRequest->get('field_requester_email')->value;
    $this->assertEquals($requesterEmailAddress, $testRequesterEmailAddress, 'FOIA Request created with no or incorrect requester email.');
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
   * Sets up a FOIA request for testing.
   */
  protected function setupFoiaRequestEntity() {
    $fields = [
      'field_webform_submission_id',
      'field_agency_component',
      'field_requester_email',
    ];
    $this->installFieldsOnEntity($fields, 'foia_request', 'foia_request');
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
