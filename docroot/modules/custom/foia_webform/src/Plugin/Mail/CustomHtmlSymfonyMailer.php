<?php

namespace Drupal\foia_webform\Plugin\Mail;

use Drupal\Core\Mail\Attribute\Mail;
use Drupal\Core\Mail\Plugin\Mail\SymfonyMailer;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Error;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/**
 * Sends HTML emails using Symfony Mailer.
 */
#[Mail(
  id: 'custom_html_symfony_mailer',
  label: new TranslatableMarkup('Custom HTML Symfony Mailer'),
)]
class CustomHtmlSymfonyMailer extends SymfonyMailer {

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
    \Drupal::logger('foia_webform')->notice(
      'Custom mail received @count attachments',
      [
        '@count' => count($message['attachments'] ?? []),
      ]
    );
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

      \Drupal::logger('foia_webform')->notice(
        'Top-level attachments: @top Params attachments: @params',
        [
          '@top' => count($message['attachments'] ?? []),
          '@params' => count($message['params']['attachments'] ?? []),
        ]
      );

      $attachments = $message['attachments']
        ?? $message['params']['attachments']
        ?? [];

      foreach ($attachments as $attachment) {
        \Drupal::logger('foia_webform')->notice(
          'Attachment @name mime=@mime bytes=@bytes',
          [
            '@name' => $attachment['filename'] ?? 'missing',
            '@mime' => $attachment['filemime'] ?? 'missing',
            '@bytes' => strlen($attachment['filecontent'] ?? ''),
          ]
        );
        $email->addPart(new DataPart(
          $attachment['filecontent'],
          $attachment['filename'] ?? NULL,
          $attachment['filemime'] ?? NULL
        ));
      }

      $mailer = $this->getMailer();
      \Drupal::logger('foia_webform')->notice(
        'Symfony email attachments: @count',
        [
          '@count' => count($email->getAttachments()),
        ]
      );
      $mailer->send($email);

      return TRUE;
    }
    catch (\Exception $e) {
      Error::logException($this->logger, $e);
      return FALSE;
    }
  }

}
