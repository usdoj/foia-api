<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_webform\AgencyLookupServiceInterface;
use Drupal\foia_webform\FoiaSubmissionServiceFactoryInterface;
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
   * The factory class to build the submission.
   *
   * @var \Drupal\foia_webform\FoiaSubmissionServiceFactoryInterface
   */
  protected $foiaSubmissionServiceFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(WebformSubmissionStorageInterface $webformStorage, AgencyLookupServiceInterface $agencyLookupService, FoiaSubmissionServiceFactoryInterface $foiaSubmissionServiceFactory) {
    $this->webformStorage = $webformStorage;
    $this->agencyLookUpService = $agencyLookupService;
    $this->foiaSubmissionServiceFactory = $foiaSubmissionServiceFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager')->getStorage('webform_submission'),
      $container->get('foia_webform.agency_lookup_service'),
      $container->get('foia_webform.foia_submission_service_factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $foiaRequest = FoiaRequest::load($data->id);

    $webformSubmission = $this->webformStorage->load($foiaRequest->get('field_webform_submission_id')->value);
    $webform = $webformSubmission->getWebform();
    $agencyComponent = $this->agencyLookUpService->getComponentFromWebform($webform->id());

    // Check the submission preference for the Agency Component.
    $submissionService = $this->foiaSubmissionServiceFactory->get($agencyComponent);

    // Submit the form values to the Agency Component.
    $validSubmissionResponse = $submissionService->sendSubmissionToComponent($webformSubmission, $webform, $agencyComponent);
  }

}
