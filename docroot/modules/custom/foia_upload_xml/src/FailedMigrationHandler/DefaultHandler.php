<?php

namespace Drupal\foia_upload_xml\FailedMigrationHandler;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

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
   *   Exception.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   Migration plugin.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   Translation.
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
