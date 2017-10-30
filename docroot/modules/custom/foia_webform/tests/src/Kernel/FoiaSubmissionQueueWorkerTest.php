<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\foia_webform\FoiaSubmissionServiceApi;
use Drupal\foia_webform\Plugin\QueueWorker\FoiaSubmissionQueueWorker;
use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_webform\FoiaSubmissionServiceFactoryInterface;

/**
 * Class FoiaSubmissionQueueWorkerTest
 */
class FoiaSubmissionQueueWorkerTest extends FoiaSubmissionServiceApiTest {

  /**
   * Test queue worker
   *
   * @var FoiaSubmissionQueueWorker
   */
  protected $queueWorker;

  /**
   * Test submission service factory.
   *
   * @var FoiaSubmissionServiceFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
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
    ] ;
    $this->setupHttpClientMock($responseContents, 200);
    $this->setupSubmissionServiceFactoryMock();
    $this->queueWorker = new FoiaSubmissionQueueWorker($this->foiaSubmissionServiceFactory);
    $data = $this->foiaSubmissionsQueue->claimItem()->data;
    $foiaRequestId = $data->id;
    $foiaRequest = FoiaRequest::load($foiaRequestId);
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());
    $this->queueWorker->processItem($data);
    $this->assertEquals(FoiaRequestInterface::STATUS_SUBMITTED, $foiaRequest->getRequestStatus());

  }

  public function testProcessingFailedSubmission() {

  }

  protected function setupSubmissionServiceFactoryMock() {
    $submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    // Tests getComponentFromWebform.
    $this->foiaSubmissionServiceFactory = $this->getMockBuilder('Drupal\foia_webform\FoiaSubmissionServiceFactory')
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();
    $this->foiaSubmissionServiceFactory->expects($this->once())
      ->method('get')
      ->will($this->returnValue($submissionServiceApi));
  }

}
