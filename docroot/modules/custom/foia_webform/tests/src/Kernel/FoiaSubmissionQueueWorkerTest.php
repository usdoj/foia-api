<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\Memory;
use Drupal\foia_webform\FoiaSubmissionServiceApi;
use Drupal\foia_webform\Plugin\QueueWorker\FoiaSubmissionQueueWorker;
use Drupal\foia_webform\Plugin\QueueWorker;
use Drupal\KernelTests\KernelTestBase;
use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\webform\WebformSubmissionStorage;
use Drupal\webform\WebformSubmissionStorageInterface;
use Drupal\foia_webform\FoiaSubmissionServiceFactoryInterface;
use Drupal\Core\Cron;
use Drupal\Core\Queue\QueueWorkerManager;

class FoiaSubmissionQueueWorkerTest extends FoiaSubmissionServiceApiTest {

  /**
   * Test queue worker
   *
   * @var FoiaSubmissionQueueWorker
   */
  protected $queueWorker;

  /**
   * Test webform storage.
   *
   * @var WebformSubmissionStorageInterface
   */
  protected $webformStorage;

  /**
   * Test submission service factory.
   *
   * @var FoiaSubmissionServiceFactoryInterface
   */
  protected $foiaSubmissionServiceFactory;

  /**
   * Test entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Test language.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language;

  /**
   * Test user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface::ANONYMOUS_ROLE
   */
  protected $user;

  /**
   * Test entity type.
   *
   * @var WebformSubmissionStorageInterface
   */
  protected $entityType;

  /**
   * Sets up parent setup.
   */
  protected function setUp() {
    parent::setUp();
    $this->setupWebformStorage();
    $this->setupEntityType();
  }

  /**
   * Tests FOIA submission queue processing.
   */
  public function testProcessItem() {
    $this->foiaRequest->set('field_webform_submission_id', $this->webformSubmission->id());
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ] ;
    $this->setupHttpClientMock($responseContents, 200);
    //$this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);

    //Cron::processQueues();

    $data = [

    ];
    //$queueWorker = FoiaSubmissionQueueWorker::create($this->container,);

    // $this->queueWorkerManager = new QueueWorkerManager->createInstance();

    $this->queueWorker = new FoiaSubmissionQueueWorker(/*WebformSubmissionStorageInterface::PURGE_NONE*/
      $this->webformStorage,
      $this->agencyLookupService,
      $this->foiaSubmissionServiceFactory
    );

    print_r($this->queueWorker);

//    $this->queueWorker = new FoiaSubmissionQueueWorker(
//      $this->webformStorage,
//      $this->agencyLookupService,
//      $this->foiaSubmissionServiceFactory
//    );
//    //$this->queueWorker = FoiaSubmissionQueueWorker::create($this->container, $this->config(''),)
//    $data = [
//      'id' => 33,
//    ];
//    $processedItem = $this->queueWorker->processItem($data);
//    print_r($processedItem);
  }

  /**
   * Sets up test webform storage.
   */
  protected function setupWebformStorage() {
    $this->webformStorage = new WebformSubmissionStorage(
      $this->entityType/* = EntityTypeInterface::BUNDLE_MAX_LENGTH*/,
      Database::getConnection(),
      $this->entityManager,
      \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT,
      $this->language,
      $this->user
    );

  }

  protected function setupEntityType() {
    $this->entityType = 'webform';
  }

}
