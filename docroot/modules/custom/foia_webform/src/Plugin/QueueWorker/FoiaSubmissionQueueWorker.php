<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\foia_webform\AgencyLookupServiceInterface;
use Drupal\foia_webform\FoiaSubmissionServiceFactoryInterface;
use Drupal\foia_webform\FoiaSubmissionServiceInterface;
use Drupal\webform\WebformSubmissionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides functionality for working with the queued FOIA form submissions.
 *
 * @QueueWorker (
 *   id = "foia_submissions",
 *   title = @Translation("FOIA Submission Queue Worker"),
 * )
 */
class FoiaSubmissionQueueWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The webform storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorage
   */
  protected $webformStorage;

  /**
   * The service to look up Agencies associated with forms.
   *
   * @var \Drupal\foia_webform\AgencyLookupServiceInterface
   */
  protected $agencyLookUpService;

  /**
   * The service to submit the submissions to the Component API.
   *
   * @var \Drupal\foia_webform\FoiaSubmissionServiceApi
   */
  protected $foiaSubmissionServiceApi;

  /**
   * The service to submit the submission via email to the Component.
   *
   * @var \Drupal\foia_webform\FoiaSubmissionServiceApi
   */
  protected $foiaSubmissionServiceEmail;

  /**
   * The factory class to build the submission.
   *
   * @var \Drupal\foia_webform\FoiaSubmissionServiceApi
   */
  protected $foiaSubmissionServiceFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(WebformSubmissionStorageInterface $webformStorage, AgencyLookupServiceInterface $agencyLookupService, FoiaSubmissionServiceInterface $foiaSubmissionApiService, FoiaSubmissionServiceInterface $foiaSubmissionEmailService, FoiaSubmissionServiceFactoryInterface $foiaSubmissionServiceFactory) {
    $this->webformStorage = $webformStorage;
    $this->agencyLookUpService = $agencyLookupService;
    $this->foiaSubmissionServiceApi = $foiaSubmissionApiService;
    $this->foiaSubmissionServiceEmail = $foiaSubmissionEmailService;
    $this->foiaSubmissionServiceFactory = $foiaSubmissionServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('webform_submission'),
      $container->get('foia_webform.agency_lookup_service'),
      $container->get('foia_webform.foia_submission_service_api'),
      $container->get('foia_webform.foia_submission_service_email'),
      $container->get('foia_webform.foia_submission_service_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $webformSubmission = $this->webformStorage->load($data->sid);

    $agencyComponent = $this->agencyLookUpService->getComponentFromWebform($webformSubmission->getWebform()->id());
    $submissionPreference = $agencyComponent->get('field_portal_submission_format')->value;
    if ($submissionPreference == 'api') {
      $this->foiaSubmissionServiceApi->sendSubmissionToComponent($webformSubmission, $agencyComponent);
    }
    else {
      $this->foiaSubmissionServiceEmail->sendSubmissionToComponent($webformSubmission, $agencyComponent);
    }
  }

}
