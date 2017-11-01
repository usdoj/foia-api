<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_webform\FoiaSubmissionServiceApi;
use Drupal\foia_webform\FoiaSubmissionServiceInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\node\Entity\NodeType;
use Drupal\node\Entity\Node;
use Drupal\webform\WebformInterface;
use Drupal\file\Entity\File;

/**
 * Class FoiaSubmissionServiceApiTest.
 *
 * Tests the FoiaSubmissionServiceApi.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
class FoiaSubmissionServiceApiTest extends FoiaWebformKernelTestBase {

  /**
   * Test agency.
   *
   * @var \Drupal\taxonomy\Entity\Term
   */
  protected $agency;

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
   * Submission Service Api.
   *
   * @var \Drupal\foia_webform\FoiaSubmissionServiceApi
   */
  protected $submissionServiceApi;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests receiving an error response from an agency component.
   */
  public function testErrorResponseFromComponent() {
    $this->foiaRequest->set('field_webform_submission_id', $this->webformSubmission->id());
    $responseContents = [
      'code' => 'A234',
      'message' => 'agency component not found',
      'description' => 'description of the error that is specific to the case management system',
    ];
    $this->setupHttpClientRequestExceptionMock($responseContents, 404);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $validSubmission = $this->submissionServiceApi->sendRequestToComponent($this->foiaRequest, $this->agencyComponent);
    $errorMessage = $this->submissionServiceApi->getSubmissionErrors();
    $this->assertEquals(FALSE, $validSubmission);
    $this->assertEquals(404, $errorMessage['response_code']);
    $this->assertEquals($responseContents['message'], $errorMessage['message']);
    $this->assertEquals($responseContents['description'], $errorMessage['description']);
  }

  /**
   * Tests generic exceptions that are thrown.
   */
  public function testExceptionPostingToComponent() {
    $this->foiaRequest->set('field_webform_submission_id', $this->webformSubmission->id());
    $exceptionMessage = 'A generic exception message.';
    $this->setupHttpClientExceptionMock($exceptionMessage);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $validSubmission = $this->submissionServiceApi->sendRequestToComponent($this->foiaRequest, $this->agencyComponent);
    $error = $this->submissionServiceApi->getSubmissionErrors();
    $this->assertEquals(FALSE, $validSubmission);
    $this->assertEquals("Exception code: 0. Exception message: {$exceptionMessage}", $error['message']);
  }

  /**
   * Tests the assembly of request data.
   */
  public function testAssesmbleRequestData() {
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $webform = Webform::create([
      'id' => $this->randomMachineName(),
    ]);
    $webform->set('foia_template', 1);
    $webform->save();
    $this->deleteWebformHandlers($webform);
    $webformSubmissionData = [
      'first_name' => 'Another',
      'last_name' => 'Test',
      'email' => 'test@test.com',
      'request_description' => 'The best request',
      'request_fee_waiver' => 'yes',
      'request_expedited_processing' => 'no',
    ];
    $webformSubmission = WebformSubmission::create([
      'webform_id' => $webform->id(),
      'data' => $webformSubmissionData,
    ]);
    $webformSubmission->save();
    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery();
    $query->condition('webform_id', $webform->id());
    foreach (\Drupal::entityTypeManager()->getStorage('webform_submission')->loadMultiple($query->execute()) as $submission) {
      $webformSubmission = $submission;
    }
    $apiVersion = ['version' => FoiaSubmissionServiceInterface::VERSION];
    $requestId = ['request_id' => $this->foiaRequest->id()];
    $agencyInfo = [
      'agency' => $this->agency->label(),
      'agency_component_name' => $this->agencyComponent->label(),
    ];
    $expectedData = array_merge($apiVersion, $requestId, $agencyInfo, $webformSubmissionData);

    $this->setProtectedProperty($this->submissionServiceApi, 'agencyComponent', $this->agencyComponent);
    $this->foiaRequest->set('field_webform_submission_id', $webformSubmission->id());
    $assembledData = $this->invokeMethod($this->submissionServiceApi, 'assembleRequestData', [$this->foiaRequest]);
    $this->assertEquals($expectedData, $assembledData);
  }

  /**
   * Tests the assembly of request data with attachments.
   */
  public function testAssesmbleRequestDataWithAttachments() {
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $webform = Webform::create([
      'id' => 'a_test_webform',
    ]);

    $config = \Drupal::config('webform_template.settings')->get('webform_template_elements');
    $templateElements = yaml_parse($config);
    $webform->setElements($templateElements);
    $webform->save();
    $this->deleteWebformHandlers($webform);

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
      'first_name' => 'Another',
      'last_name' => 'Test',
      'email' => 'test@test.com',
      'request_description' => 'The best request',
      'request_fee_waiver' => 'no',
      'request_expedited_processing' => 'no',
      'attachments_supporting_documentation' => [$file->id()],
    ];

    $webformSubmission = WebformSubmission::create([
      'webform_id' => $webform->id(),
      'data' => $webformSubmissionData,
    ]);
    $webformSubmission->save();

    $query = \Drupal::entityTypeManager()->getStorage('webform_submission')->getQuery();
    $query->condition('webform_id', $webform->id());
    foreach (\Drupal::entityTypeManager()->getStorage('webform_submission')->loadMultiple($query->execute()) as $submission) {
      $webformSubmission = $submission;
    }

    $webformSubmissionWithFileContents = [
      'first_name' => 'Another',
      'last_name' => 'Test',
      'email' => 'test@test.com',
      'request_description' => 'The best request',
      'request_fee_waiver' => 'no',
      'request_expedited_processing' => 'no',
      'attachments_supporting_documentation' => [
        [
          'content_type' => $file->getMimeType(),
          'filedata' => base64_encode(file_get_contents($file->getFileUri())),
          'filename' => $file->getFilename(),
          'filesize' => $file->getSize(),
        ],
      ],
    ];
    $apiVersion = ['version' => FoiaSubmissionServiceInterface::VERSION];
    $requestId = ['request_id' => $this->foiaRequest->id()];
    $agencyInfo = [
      'agency' => $this->agency->label(),
      'agency_component_name' => $this->agencyComponent->label(),
    ];
    $expectedData = array_merge($apiVersion, $requestId, $agencyInfo, $webformSubmissionWithFileContents);

    $this->setProtectedProperty($this->submissionServiceApi, 'agencyComponent', $this->agencyComponent);
    $this->foiaRequest->set('field_webform_submission_id', $webformSubmission->id());
    $assembledData = $this->invokeMethod($this->submissionServiceApi, 'assembleRequestData', [$this->foiaRequest]);
    $this->assertEquals($expectedData, $assembledData);
  }

  /**
   * Tests receiving a successful response from an agency component.
   */
  public function testSuccessResponseFromComponent() {
    $this->foiaRequest->set('field_webform_submission_id', $this->webformSubmission->id());
    $responseContents = [
      'id' => 33,
      'status_tracking_number' => 'doj-1234',
    ];
    $this->setupHttpClientMock($responseContents, 200);
    $this->submissionServiceApi = new FoiaSubmissionServiceApi($this->httpClient, $this->agencyLookupService, $this->logger);
    $validSubmission = $this->submissionServiceApi->sendRequestToComponent($this->foiaRequest, $this->agencyComponent);
    $submissionError = $this->submissionServiceApi->getSubmissionErrors();
    $this->assertNotEquals(FALSE, $validSubmission);
    $this->assertEquals(200, $validSubmission['response_code']);
    $this->assertEquals($responseContents['id'], $validSubmission['id']);
    $this->assertEquals($responseContents['status_tracking_number'], $validSubmission['status_tracking_number']);
    $this->assertEquals('api', $validSubmission['type']);
    $this->assertEquals([], $submissionError);
  }

  /**
   * Deletes webform handlers to disable queuing of FOIA requests.
   *
   * @param \Drupal\webform\WebformInterface &$webform
   *   The webform to remove handlers for.
   */
  protected function deleteWebformHandlers(WebformInterface &$webform) {
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      $webform->deleteWebformHandler($handler);
    }
    $webform->save();
  }

}
