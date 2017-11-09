<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_request\Entity\FoiaRequestInterface;

/**
 * Class FoiaSubmissionServiceApiTest.
 *
 * Tests the FoiaSubmissionServiceApi.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
class FoiaSubmissionServiceQueueHandlerTest extends FoiaWebformKernelTestBase {

  /**
   * The foia submissions queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $foiaSubmissionsQueue;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('node', ['node_access']);
    $this->foiaSubmissionsQueue = \Drupal::service('queue')->get('foia_submissions');
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
    $data = ['email' => $testRequesterEmailAddress];
    $this->setupWebformSubmission(NULL, $data);

    $queuedSubmission = $this->foiaSubmissionsQueue->claimItem()->data;
    $this->assertNotEmpty($queuedSubmission, "Expected a FOIA request ID to be queued, but nothing was found in the queue.");

    $foiaRequest = FoiaRequest::load($queuedSubmission->id);
    $requesterEmailAddress = $foiaRequest->get('field_requester_email')->value;
    $this->assertEquals($requesterEmailAddress, $testRequesterEmailAddress, 'FOIA Request created with no or incorrect requester email.');
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

}
