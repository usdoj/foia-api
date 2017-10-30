<?php

namespace Drupal\foia_webform;

use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandlerManagerInterface;
use Egulias\EmailValidator\EmailValidator;

/**
 * Class FoiaSubmissionServiceEmail.
 */
class FoiaSubmissionServiceEmail implements FoiaSubmissionServiceInterface {

  /**
   * The Agency Lookup service.
   *
   * @var \Drupal\foia_webform\AgencyLookupServiceInterface
   */
  protected $agencyLookupService;

  /**
   * The agency component node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $agencyComponent;

  /**
   * The webform handler manager service.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerManagerInterface
   */
  protected $webformHandlerManager;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Submission-related error messages.
   *
   * @var array
   */
  protected $errors;

  /**
   * FoiaSubmissionServiceEmail constructor.
   *
   * @param \Drupal\foia_webform\AgencyLookupServiceInterface $agencyLookupService
   *   The Agency Lookup service.
   * @param \Drupal\webform\Plugin\WebformHandlerManagerInterface $webformHandlerManager
   *   The webform handler manager.
   * @param \Egulias\EmailValidator\EmailValidator $emailValidator
   *   The email validator.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(AgencyLookupServiceInterface $agencyLookupService, WebformHandlerManagerInterface $webformHandlerManager, EmailValidator $emailValidator, LoggerInterface $logger) {
    $this->agencyLookupService = $agencyLookupService;
    $this->webformHandlerManager = $webformHandlerManager;
    $this->emailValidator = $emailValidator;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function sendRequestToComponent(FoiaRequestInterface $foiaRequest, NodeInterface $agencyComponent) {
    $this->agencyComponent = $agencyComponent;
    $componentEmailAddress = $agencyComponent->get('field_submission_email')->value;

    if (!$componentEmailAddress) {
      $error['message'] = 'Missing Submission Email Address for component.';
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }

    if (!$this->emailValidator->isValid($componentEmailAddress)) {
      $error['message'] = 'Invalid Email Address for the component.';
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }

    $emailToSend = $this->assembleEmailMessage($foiaRequest);
    return $this->sendEmailToComponent($emailToSend, $componentEmailAddress);
  }

  /**
   * Gathers the required fields for the API request.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request being submitted.
   *
   * @return array
   *   Return the assembled request data in an array.
   */
  protected function assembleEmailMessage(FoiaRequestInterface $foiaRequest) {
    // Get the webform submission values.
    $emailMessage = $this->getEmailToSend($foiaRequest);

    $foiaRequestId = ['request_id' => $foiaRequest->id()];
    $submissionValues = array_merge($foiaRequestId, $emailMessage);

    return $submissionValues;
  }

  /**
   * Return the form submission values as an array.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request being submitted.
   *
   * @return array
   *   Returns the submission values as an array.
   */
  protected function getEmailToSend(FoiaRequestInterface $foiaRequest) {
    $webformSubmission = WebformSubmission::load($foiaRequest->get('field_webform_submission_id')->value);
    /** @var \Drupal\foia_webform\Plugin\WebformHandler\FoiaEmailWebformHandler $foiaEmailWebformHandler */
    $foiaEmailWebformHandler = $this->webformHandlerManager->createInstance('foia_email');
    $messageToSend = $foiaEmailWebformHandler->getEmailMessage($webformSubmission, $this->agencyComponent);
    return $messageToSend;
  }

  /**
   * Submit the FOIA request form values to the component endpoint.
   *
   * @param string $componentEndpoint
   *   The URL of the component's endpoint.
   * @param array $submissionValues
   *   An array containing the values to submit to the component endpoint.
   */
  protected function sendEmailToComponent($emailToSend, $componentEmailAddress) {

  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionErrors() {
    $submissionErrors = $this->errors;
    $submissionErrors['type'] = 'email';
    return $submissionErrors;
  }

  /**
   * Adds a submission error for later retrieval.
   *
   * @param array $error
   *   An associative array containing error information.
   */
  protected function addSubmissionError(array $error) {
    $this->errors['response_code'] = isset($error['smtp_code']) ? $error['smtp_code'] : '';;
    $this->errors['message'] = isset($error['message']) ? $error['message'] : '';
    $this->errors['description'] = isset($error['description']) ? $error['description'] : '';
  }

  /**
   * Helper function which logs with appropriate context.
   *
   * @param string $level
   *   The level with which to log (e.g. error, notice, etc.).
   * @param string $message
   *   The message to log.
   * @param array $context
   *   The message context.
   * @param string $api
   *   The relevant API.
   */
  protected function log($level, $message, array $context = [], $api = 'Submission Service Email') {
    $context['@agency_component_id'] = $this->agencyComponent->id();
    $this->logger->log($level, "{$api}: Agency Component: @agency_component_id. {$message}", $context);
  }

}
