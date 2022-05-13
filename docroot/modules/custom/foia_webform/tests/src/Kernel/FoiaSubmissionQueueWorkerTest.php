<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\foia_webform\FoiaSubmissionServiceApi;
use Drupal\foia_webform\Plugin\QueueWorker\FoiaSubmissionQueueWorker;
use Drupal\foia_request\Entity\FoiaRequest;

/**
 * Class FoiaSubmissionQueueWorkerTest.
 *
 * Testing of webform submission.
 *
 * @package Drupal\Tests\foia_webform\Kernel
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
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\foia_webform\FoiaSubmissionServiceFactoryInterface
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
   * Tests FOIA submission queue processing after successful API submission.
   */
  public function testProcessingSuccessfulApiSubmission() {
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->setupSubmissionServiceFactoryMock('api');
    $this->queueWorker = new FoiaSubmissionQueueWorker($this->foiaSubmissionServiceFactory);

    $data = $this->foiaSubmissionsQueue->claimItem()->data;
    $foiaRequestId = $data->id;
    $foiaRequest = FoiaRequest::load($foiaRequestId);
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());
    $this->queueWorker->processItem($data);
    $this->assertEquals(FoiaRequestInterface::STATUS_SUBMITTED, $foiaRequest->getRequestStatus());
    $this->assertEquals(FoiaRequestInterface::METHOD_API, $foiaRequest->getSubmissionMethod());
    $caseManagementId = $foiaRequest->get('field_case_management_id')->value;
    $this->assertEquals($responseContents['id'], $caseManagementId);
    $statusTrackingNumber = $foiaRequest->get('field_tracking_number')->value;
    $this->assertEquals($responseContents['status_tracking_number'], $statusTrackingNumber);
  }

  /**
   * Tests FOIA submission queue processing after failed API submission.
   */
  public function testProcessingFailedApiSubmission() {
    $responseContents = [
      'code' => 'error99',
      'message' => 'error message',
      'description' => 'error description',
    ];
    $this->setupHttpClientMock($responseContents, 404);
    $this->setupSubmissionServiceFactoryMock('api');
    $this->queueWorker = new FoiaSubmissionQueueWorker($this->foiaSubmissionServiceFactory);

    $data = $this->foiaSubmissionsQueue->claimItem()->data;
    $foiaRequestId = $data->id;
    $foiaRequest = FoiaRequest::load($foiaRequestId);
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());
    $this->queueWorker->processItem($data);
    $this->assertEquals(FoiaRequestInterface::STATUS_FAILED, $foiaRequest->getRequestStatus());
    $this->assertEquals(FoiaRequestInterface::METHOD_API, $foiaRequest->getSubmissionMethod());
    $errorCode = $foiaRequest->get('field_error_code')->value;
    $this->assertEquals($responseContents['code'], $errorCode);
    $errorMessage = $foiaRequest->get('field_error_message')->value;
    $this->assertEquals($responseContents['message'], $errorMessage);
    $errorDescription = $foiaRequest->get('field_error_description')->value;
    $this->assertEquals($responseContents['description'], $errorDescription);
    $responseCode = $foiaRequest->get('field_response_code')->value;
    $this->assertEquals(404, $responseCode);
    $timeStamp = $foiaRequest->get('field_submission_time')->value;
    $this->assertNotEmpty($timeStamp);
  }

  /**
   * Tests FOIA submission queue processing after successful Email submission.
   */
  public function testProcessingSuccessfulEmailSubmission() {
    $this->setupSubmissionServiceFactoryMock('email', ['type' => FoiaRequestInterface::METHOD_EMAIL]);
    $this->queueWorker = new FoiaSubmissionQueueWorker($this->foiaSubmissionServiceFactory);

    $data = $this->foiaSubmissionsQueue->claimItem()->data;
    $foiaRequestId = $data->id;
    $foiaRequest = FoiaRequest::load($foiaRequestId);
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());
    $this->queueWorker->processItem($data);
    $this->assertEquals(FoiaRequestInterface::STATUS_IN_TRANSIT, $foiaRequest->getRequestStatus());
    $this->assertEquals(FoiaRequestInterface::METHOD_EMAIL, $foiaRequest->getSubmissionMethod());
    $timeStamp = $foiaRequest->get('field_submission_time')->value;
    $this->assertNotEmpty($timeStamp);
  }

  /**
   * Tests FOIA submission queue processing after failed Email submission.
   */
  public function testProcessingFailedEmailSubmission() {
    $this->setupSubmissionServiceFactoryMock('email', FALSE);
    $this->queueWorker = new FoiaSubmissionQueueWorker($this->foiaSubmissionServiceFactory);

    $data = $this->foiaSubmissionsQueue->claimItem()->data;
    $foiaRequestId = $data->id;
    $foiaRequest = FoiaRequest::load($foiaRequestId);
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());
    $this->queueWorker->processItem($data);
    $this->assertEquals(FoiaRequestInterface::STATUS_FAILED, $foiaRequest->getRequestStatus());
    $this->assertEquals(FoiaRequestInterface::METHOD_EMAIL, $foiaRequest->getSubmissionMethod());
    $timeStamp = $foiaRequest->get('field_submission_time')->value;
    $this->assertNotEmpty($timeStamp);
  }

  /**
   * Sets up a mock for the submission service factory.
   *
   * @param string $type
   *   The type of submission service that should be created, defaults to email.
   * @param string $response
   *   What the email submission service should return when invoked.
   */
  protected function setupSubmissionServiceFactoryMock($type, $response = '') {
    if ($type == 'api') {
      $submissionService = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    }
    else {
      $submissionService = $this->getMockBuilder('Drupal\foia_webform\FoiaSubmissionServiceEmail')
        ->disableOriginalConstructor()
        ->setMethods(['sendRequestToComponent'])
        ->getMock();
      $submissionService->expects(($this->any()))
        ->method('sendRequestToComponent')
        ->willReturn($response);
    }
    $this->foiaSubmissionServiceFactory = $this->getMockBuilder('Drupal\foia_webform\FoiaSubmissionServiceFactory')
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();
    $this->foiaSubmissionServiceFactory->expects($this->any())
      ->method('get')
      ->will($this->returnValue($submissionService));
  }

}
