<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\foia_webform\FoiaSubmissionServiceApi;
use Drupal\foia_webform\Plugin\QueueWorker\FoiaSubmissionQueueWorker;
use Drupal\foia_request\Entity\FoiaRequest;

/**
 * Class FoiaSubmissionQueueWorkerTest.
 *
 * @group local
 */
class FoiaSubmissionQueueWorkerTest extends FoiaSubmissionServiceApiTest {

  /**
   * Test queue worker.
   *
   * @var \Drupal\foia_webform\Plugin\QueueWorker\FoiaSubmissionQueueWorker
   */
  protected $queueWorker;

  /**
   * Test submission service factory.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|FoiaSubmissionServiceFactoryInterface
   */
  protected $foiaSubmissionServiceFactory;

  /**
   * The foia submissions queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $foiaSubmissionsQueue;

  /**
   * Sets up parent setup.
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('node', ['node_access']);
    $this->foiaSubmissionsQueue = \Drupal::service('queue')->get('foia_submissions');
  }

  /**
   * Tests FOIA submission queue processing.
   */
  public function testProcessingSuccessfulSubmission() {
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
      // @TODO test email METHOD_API VVV.
      'type' => FoiaRequestInterface::METHOD_API,
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->setupSubmissionServiceFactoryMock();
    $this->queueWorker = new FoiaSubmissionQueueWorker($this->foiaSubmissionServiceFactory);
    $data = $this->foiaSubmissionsQueue->claimItem()->data;
    $foiaRequestId = $data->id;
    $foiaRequest = FoiaRequest::load($foiaRequestId);
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());
    $this->queueWorker->processItem($data);
    $this->assertEquals(FoiaRequestInterface::STATUS_SUBMITTED, $foiaRequest->getRequestStatus());
    $caseManagementId = $foiaRequest->get('field_case_management_id')->getString();
    $this->assertEquals('33', $caseManagementId);
    $trackingNumber = $foiaRequest->get('field_tracking_number')->getString();
    $this->assertEquals('doj-1234', $trackingNumber);
    $type = $foiaRequest->getSubmissionMethod();
    $this->assertEquals(FoiaRequestInterface::METHOD_API, $type);

  }

  /**
   * Tests FOIA submission queue processing failures.
   */
  public function testProcessingFailedSubmission() {
    $responseContents = [
      'id' => 66,
      'status_tracking_number' => 'doj-5678',
      'type' => FoiaRequestInterface::METHOD_EMAIL,

    ];
    $this->setupHttpClientMock($responseContents, 400);
    $this->setupSubmissionServiceFactoryMock();
    $this->queueWorker = new FoiaSubmissionQueueWorker($this->foiaSubmissionServiceFactory);
    $data = $this->foiaSubmissionsQueue->claimItem()->data;
    $foiaRequestId = $data->id;
    $foiaRequest = FoiaRequest::load($foiaRequestId);
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());

  }

  /**
   * Creates test double for submission service factory.
   */
  protected function setupSubmissionServiceFactoryMock() {
    $submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    // Tests getComponentFromWebform.
    $this->foiaSubmissionServiceFactory = $this->getMockBuilder('Drupal\foia_webform\FoiaSubmissionServiceFactory')
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();
    $this->foiaSubmissionServiceFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValue($submissionServiceApi));
  }

}
