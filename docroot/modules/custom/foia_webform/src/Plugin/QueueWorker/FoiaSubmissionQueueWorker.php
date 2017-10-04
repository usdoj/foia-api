<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
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
   * @var \Drupal\webform\WebformStorageInterface
   */
  protected $webformStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(WebformStorageInterface $webformStorage) {
    $this->webformStorage = $webformStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('webform_submission')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {}

}
