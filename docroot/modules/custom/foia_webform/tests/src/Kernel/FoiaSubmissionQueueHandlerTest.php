<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Class FoiaSubmissionServiceApiTest.
 *
 * Tests the FoiaSubmissionServiceApi.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
class FoiaSubmissionServiceQueueHandlerTest extends FoiaWebformKernelTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

  }

  /**
   * Tests that a FOIA request ID is queued when a webform is submitted.
   */
  public function testFoiaRequestCreatedAndQueuedOnWebformSubmission() {
    $item = $this->foiaSubmissionsQueue->claimItem();
    $this->foiaSubmissionsQueue->deleteItem($item);
    $queuedSubmission = $this->foiaSubmissionsQueue->claimItem()->data;
    print_r($queuedSubmission);
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
   * Tests request not queued when a webform is not associated to a component.
   */
  public function testFoiaRequestIdNotQueuedOnWebformSubmission() {
    $this->agencyComponent->field_request_submission_form->target_id = '';
    $this->agencyComponent->save();
    $this->setupWebformSubmission();
    $this->foiaSubmissionsQueue->claimItem();
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

}
