<?php

namespace Drupal\foia_quarterly_data_report\Plugin\Action;

/**
 * @file
 * Contains \Drupal\foia_quarterly_data_report\Plugin\Action\ModerationQRaction.
 */

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * VBO Moderation state change.
 *
 * @Action(
 *   id = "moderation_quarterly_report_action",
 *   label = @Translation("Publish foia quarterly reports"),
 *   type = "node",
 * )
 */
class ModerationQRaction extends ActionBase implements ContainerFactoryPluginInterface {
  /**
   * Current user.
   *
   * @var current_user
   */
  protected $currentUser;
  /**
   * Message.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  /**
   * Work Flow.
   *
   * @var \Drupal\foia_quarterly_data_report\target_states
   */
  protected $workflow;

  /**
   * ModerationQRaction constructor.
   *
   * @param array $configuration
   *   Config service.
   * @param string $plugin_id
   *   Plugin id service.
   * @param string $plugin_definition
   *   Plugin definition service.
   * @param object $user
   *   Current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param string $target_states
   *   Target_states service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $user, MessengerInterface $messenger, $target_states) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $user;
    $this->messenger = $messenger;
    $this->workflow = $target_states;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('current_user'), $container->get('messenger'), $container->get('vbo.target.states.service'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if (is_array($entity)) {
      $this->executeMultiple($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {

    $operations = [];
    $target_state = 'published';
    foreach ($entities as $entity) {
      $node = $this->workflow->getLatestRevision($entity);
      $operations[] = ['update_moderation_state', [$node, $target_state]];
    }

    // Initialize batch (to set title).
    $batch = [
      'title' => t('Updating Moderation State'),
      'operations' => $operations,
      'finished' => 'process_batch_finished',
      'init_message' => t('Bulk operation of moderation state change is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The bulk operation process has encountered an error.'),
      'file' => \Drupal::service('extension.list.module')->getPath('foia_quarterly_data_report') . '/batch.inc',
    ];
    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $user = !isset($account) ? $this->currentUser : $account;
    if ($object->getEntityTypeId() !== 'node') {
      $this->messenger->addError($this->t('Can only perform publishing on FOIA quarterly report node content.'));
      return FALSE;
    };

    if ($object->bundle() != 'quarterly_foia_report_data') {
      $this->messenger->addError($this->t('Can only perform publishing on FOIA quarterly report content.'));
      return FALSE;
    }

    if ($object->moderation_state->value != 'submitted_to_oip') {
      $this->messenger->addError($this->t('Current moderation state is not a valide state for publishing.'));
      return FALSE;
    }

    if (!$user->hasPermission('use quarterly report workflow transition publish')) {
      $this->messenger->addError($this->t('User: %name does not have access to execute Moderation state change.', ['%name' => $user->getDisplayName()]));
      return FALSE;
    }

    $field_agency = $object->get('field_agency')->getString();
    $components = $object->get('field_agency_components')->getString();
    if (empty($field_agency) || empty($components)) {
      $this->messenger->addError($this->t('The agency and component field values must be validate for publishing.'));
      return FALSE;
    }

    $workflows = $this->workflow->getTargetStates($object);
    $target_state = array_keys($workflows['target_states'])[0];
    $valide_states = ['draft', 'published'];
    if (!in_array($target_state, $valide_states)) {
      $this->messenger->addError($this->t('Current target state is not a valide state.'));
      return FALSE;
    }
    return TRUE;
  }

}
