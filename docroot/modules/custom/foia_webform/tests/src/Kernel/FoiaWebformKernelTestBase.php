<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\webform\Entity\Webform;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Base class for FOIA Webform Kernel Tests.
 */
abstract class FoiaWebformKernelTestBase extends KernelTestBase {

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
   * Modules to enable.
   *
   * @var array
   */

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'field_permissions',
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
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('foia_request');
    $this->installEntitySchema('webform_submission');

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
   * Sets up a webform submission.
   */
  protected function setupWebformSubmission($id = NULL, $data = NULL) {
    if ($id === NULL) {
      $id = $this->webform->id();
    }
    if ($data === NULL) {
      $data = ['custom' => 'value'];
    }
    $webformSubmission = WebformSubmission::create(['webform_id' => $id, 'data' => $data]);
    $webformSubmission->save();
    $this->webformSubmission = $webformSubmission;
  }

}
