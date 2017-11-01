<?php

namespace Drupal\foia_webform;

use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandlerManagerInterface;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;

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
   * Email webform handler.
   *
   * @var \Drupal\foia_webform\Plugin\WebformHandler\FoiaEmailWebformHandler
   */
  protected $foiaEmailWebformHandler;

  /**
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission;

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
    $this->foiaEmailWebformHandler = $webformHandlerManager->createInstance('foia_email');
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

    $emailToSend = $this->assembleEmailMessage($foiaRequest, $componentEmailAddress);
    return $this->sendEmailToComponent($emailToSend);
  }

  /**
   * Assembles the contents of the email message to be sent to the component.
   *
   * @param \Drupal\foia_request\Entity\FoiaRequestInterface $foiaRequest
   *   The FOIA request being submitted.
   * @param string $componentEmailAddress
   *   The agency component's email address.
   *
   * @return array
   *   The email to send to the agency component.
   */
  protected function assembleEmailMessage(FoiaRequestInterface $foiaRequest, $componentEmailAddress) {
    $this->webformSubmission = WebformSubmission::load($foiaRequest->get('field_webform_submission_id')->value);
    $webform = $this->webformSubmission->getWebform();
    $this->foiaEmailWebformHandler->setWebform($webform);
    $messageToSend = $this->foiaEmailWebformHandler->getEmailMessage($foiaRequest->id(), $this->webformSubmission, $componentEmailAddress);
    return $messageToSend;
  }

  /**
   * @param array $emailToSend
   *
   * @return array|bool
   */
  protected function sendEmailToComponent(array $emailToSend) {
    $message = $this->foiaEmailWebformHandler->sendEmailMessage($this->webformSubmission, $emailToSend);
    if ($message['result']) {
      return [
        'type' => FoiaRequestInterface::METHOD_EMAIL,
      ];
    }
    else {
      $error['message'] = 'Failed sending email to email server.';
      $this->addSubmissionError($error);
      $this->log('warning', $error['message']);
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionErrors() {
    $submissionErrors = $this->errors;
    $submissionErrors['type'] = FoiaRequestInterface::METHOD_EMAIL;
    return $submissionErrors;
  }

  /**
   * Adds a submission error for later retrieval.
   *
   * @param array $error
   *   An associative array containing error information.
   */
  protected function addSubmissionError(array $error) {
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
