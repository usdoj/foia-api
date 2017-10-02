<?php

namespace Drupal\foia_webform\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the FoiaForm Queue Workers.
 */
abstract class FoiaFormQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The form submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $formSubmission;

  /**
   * {@inheritdoc}
   */
  public function __construct(WebformSubmissionInterface $submission) {
    $this->formSubmission = $submission;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('webform_submission')
    );
  }

}
