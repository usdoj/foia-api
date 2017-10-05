<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\foia_webform\AgencyLookupServiceInterface;
use Drupal\foia_webform\FoiaSubmissionProcessingFactoryInterface;
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
   * @var \Drupal\foia_webform\FoiaSubmissionApiService
   */
  protected $foiaSubmissionApiService;

  /**
   * The service to submit the submission via email to the Component.
   *
   * @var \Drupal\foia_webform\FoiaSubmissionApiService
   */
  protected $foiaSubmissionEmailService;

  /**
   * The factory class to build the submission.
   *
   * @var \Drupal\foia_webform\FoiaSubmissionApiService
   */
  protected $foiaSubmissionServiceFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(WebformSubmissionStorageInterface $webformStorage, AgencyLookupServiceInterface $agencyLookupService, FoiaSubmissionServiceInterface $foiaSubmissionApiService, FoiaSubmissionServiceInterface $foiaSubmissionEmailService, FoiaSubmissionProcessingFactoryInterface $foiaSubmissionServiceFactory) {
    $this->webformStorage = $webformStorage;
    $this->agencyLookUpService = $agencyLookupService;
    $this->foiaSubmissionApiService = $foiaSubmissionApiService;
    $this->foiaSubmissionApiService = $foiaSubmissionEmailService;
    $this->foiaSubmissionServiceFactory = $foiaSubmissionServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('webform_submission'),
      $container->get('foia_webform.agency_lookup_service'),
      $container->get('foia_webform.foia_submission_api_service'),
      $container->get('foia_webform.foia_submission_email_service'),
      $container->get('foia_webform.foia_submission_service_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $webformSubmission = $this->webformStorage->load($data->sid);

    $agencyComponent = $this->agencyLookUpService->getComponentByWebform($webformSubmission->getWebform()->getOriginalId());
    $submissionPreference = $agencyComponent->get('field_portal_submission_format');
    if ($submissionPreference == 'api') {
      $this->foiaSubmissionApiService->sendSubmissionToComponent($data, $agencyComponent);
    }
    else {
      $this->foiaSubmissionEmailService->sendSubmissionToComponent($data, $agencyComponent);
    }
  }

}
