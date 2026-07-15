<?php

namespace Drupal\foia_webform\Plugin\Mail;

use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Mail\Plugin\Mail\SymfonyMailer;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Sends HTML emails using Symfony Mailer.
 */
#[Mail(
  id: 'custom_html_symfony_mailer',
  label: new TranslatableMarkup('Custom HTML Symfony Mailer'),
)]
class CustomHtmlSymfonyMailer extends SymfonyMailer {

  /**
   * Logger channel for FOIA Webform operations.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a FOIA submission queue handler.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Symfony\Component\Mailer\MailerInterface $mailer
   *   Mailer.
   */
  public function __construct(
    LoggerInterface $logger,
    ?MailerInterface $mailer = NULL,
  ) {
    \Drupal::logger('foia_webform')->notice('CustomHtmlSymfonyMailer constructed.');
    parent::__construct($logger, $mailer);
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    $message['body'] = implode("\n\n", $message['body']);
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    \Drupal::logger('foia_webform')->notice('Running mail in foia_webform');
    try {
      $email = new Email();

      $headers = $email->getHeaders();
      foreach ($message['headers'] as $name => $value) {
        if (!in_array(strtolower($name), ['content-type', 'content-transfer-encoding'], TRUE)) {
          $headers->addHeader($name, $value);
        }
      }

      $recipients = array_map(trim(...), str_getcsv($message['to'], escape: "\\"));

      $email
        ->to(...$recipients)
        ->subject($message['subject'])
        ->html($message['body']);

      $mailer = $this->getMailer();
      $mailer->send($email);

      return TRUE;
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
      return FALSE;
    }
  }

}
