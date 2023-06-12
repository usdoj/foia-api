<?php

namespace Drupal\foia_upload_xml\FailedMigrationHandler;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * A default FailedMigrationHandler.
 *
 * @package Drupal\foia_upload_xml\FailedMigrationHandler
 */
class DefaultHandler implements FailedMigrationHandlerInterface {

  /**
   * The exception.
   *
   * @var \Exception
   */
  protected $exception;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $t;

  /**
   * BaseHandler constructor.
   *
   * @param \Exception $e
   *   Exception for errors.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Core messenger interface for sending messages to the user.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Core migration interface.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   Core translation interface.
   */
  public function __construct(\Exception $e, MessengerInterface $messenger, MigrationInterface $migration, TranslationInterface $translation) {
    $this->exception = $e;
    $this->messenger = $messenger;
    $this->migration = $migration;
    $this->t = $translation;
  }

  /**
   * {@inheritDoc}
   */
  public function handle() {
    $sourceFile = pathinfo($this->migration->getSourceConfiguration()['urls']);
    $this->messenger->deleteByType(MessengerInterface::TYPE_STATUS);
    $this->messenger->addError($this->t->translate('Failed to process file @file.', [
      '@file' => $sourceFile['basename'],
    ]));
  }

}
