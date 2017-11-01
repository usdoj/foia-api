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
class FoiaSubmissionQueueWorkerTest extends FoiaWebformKernelTestBase {

  use ReflectionTrait;
  use FieldInstallTrait;

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
  }

  /**
   * Tests FOIA submission queue processing.
   */
  public function testProcessingSuccessfulSubmission() {
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
      // @TODO test email METHOD_API.
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
      'type' => FoiaRequestInterface::METHOD_EMAIL,
      'error_code' => 'error99',
      'message' => 'error message',
      'description' => 'error description',
    ];
    $this->setupHttpClientMock($responseContents, 404);
    $this->setupSubmissionServiceFactoryMock();
    $this->queueWorker = new FoiaSubmissionQueueWorker($this->foiaSubmissionServiceFactory);
    $data = $this->foiaSubmissionsQueue->claimItem()->data;
    $foiaRequestId = $data->id;
    $foiaRequest = FoiaRequest::load($foiaRequestId);
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());
    $this->queueWorker->processItem($data);
    $this->assertEquals(FoiaRequestInterface::STATUS_FAILED, $foiaRequest->getRequestStatus());
    $errorCode = $foiaRequest->get('field_error_code')->getString();
    $this->assertEquals($responseContents['error_code'], $errorCode);
    $errorMessage = $foiaRequest->get('field_error_message')->getString();
    $this->assertEquals('Message: Unexpected error response format from component. Description: ', $errorMessage);

    $responseCode = $foiaRequest->get('field_response_code')->getString();
    $this->assertEquals(404, $responseCode);

    $timeStamp = $foiaRequest->get('field_submission_time')->getString();
    $this->assertNotEmpty($timeStamp);

    // no reponse code
    /*$this->setupHttpClientMock($responseContents,);
    $this*/

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
