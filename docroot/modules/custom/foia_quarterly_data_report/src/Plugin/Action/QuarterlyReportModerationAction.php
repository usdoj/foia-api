<?php

namespace Drupal\foia_quarterly_data_report\Plugin\Action;

/**
 * @file
 * Contains \Drupal\foia_quarterly_data_report\Plugin\Action\QuarterlyReportModerationAction.
 */

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * VBO Moderation state change.
 *
 * @Action(
 *   id = "moderation_quarterly_report_action",
 *   label = @Translation("Publish foia quarterly reports"),
 *   type = "node",
 * )
 */
class QuarterlyReportModerationAction extends ActionBase implements ContainerFactoryPluginInterface {
  /**
   * Message.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Validation.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $validation;

  /**
   * Moderation Info.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * QuarterlyReportModerationAction constructor.
   *
   * @param array $configuration
   *   Config service.
   * @param string $plugin_id
   *   Plugin id service.
   * @param string $plugin_definition
   *   Plugin definition service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   Moderation information service.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validation
   *   Validation information service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, ModerationInformationInterface $moderation_info, StateTransitionValidationInterface $validation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;
    $this->moderationInfo = $moderation_info;
    $this->validation = $validation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation.state_transition_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $entities) {
    $batches = [];
    foreach ($entities as $entity) {
      $node = $this->getLatestRevision($entity);
      $batches[] = [
        '\Drupal\foia_quarterly_data_report\Plugin\Action\QuarterlyReportModerationAction::batchCore',
        [$node, 'published', count($entities)],
      ];
    }

    // Initialize batch (to set title).
    $batch = [
      'title' => t('Updating Moderation State'),
      'operations' => $batches,
      'finished' => '\Drupal\foia_quarterly_data_report\Plugin\Action\QuarterlyReportModerationAction::batchFinished',
      'init_message' => t('Bulk operation of moderation state change is starting.'),
      'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
      'error_message' => t('The bulk operation process has encountered an error.'),
    ];
    batch_set($batch);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $user = !isset($account) ? \Drupal::service('current_user') : $account;
    if ($object->getEntityTypeId() !== 'node') {
      $this->messenger->addError($this->t('Can only perform publishing on node content.'));
      return FALSE;
    };

    if ($object->bundle() != 'quarterly_foia_report_data') {
      $this->messenger->addError($this->t('Can only perform publishing on FOIA quarterly report content.'));
      return FALSE;
    }

    if ($object->moderation_state->value != 'submitted_to_oip') {
      $this->messenger->addError($this->t("The report must be 'Submitted to OIP' before it can be published."));
      return FALSE;
    }

    if (!$user->hasPermission('use quarterly report workflow transition publish')) {
      $this->messenger->addError($this->t('User: %name does not have access to execute Moderation state change.', ['%name' => $user->getDisplayName()]));
      return FALSE;
    }

    $field_agency = $object->get('field_agency')->getString();
    $components = $object->get('field_agency_components')->getString();
    if (empty($field_agency) || empty($components)) {
      $this->messenger->addError($this->t('The agency and component field values must be populated for publishing.'));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Get latest revision of node.
   *
   * @param object $object
   *   Get the latest revision.
   */
  protected function getLatestRevision($object) {
    if (!isset($object)) {
      return NULL;
    }
    $vids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($object);
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadRevision(end($vids));
    return $node;
  }

  /**
   * See callback_batch_operation().
   *
   * @param Drupal\node\NodeInterface $node
   *   Content quarterly report type.
   * @param string $state
   *   Moderation state.
   * @param int $total
   *   Total number of operation.
   * @param array $context
   *   Results array.
   */
  public static function batchCore(NodeInterface $node, $state, int $total, array &$context) {
    // Initialize results for batchCore.
    if (!isset($context['results']['count'])) {
      $context['results'] = [
        'count' => 0,
        'total' => $total,
        'nids_processed' => [],
        'nids_process_failed' => [],
      ];
    }
    try {
      $entity = \Drupal::entityTypeManager()->getStorage('node')->createRevision($node, $node->isDefaultRevision());
      $entity->set('moderation_state', $state);

      if (method_exists($entity, 'setRevisionUserId')) {
        $entity->setRevisionCreationTime(REQUEST_TIME);
        $entity->setRevisionLogMessage('VBO Published quarterly report, time:' . date('d/m/Y - h:i', REQUEST_TIME));
        $entity->setRevisionUserId(\Drupal::service('current_user')->id());
      }
      if ($entity->save()) {
        $context['results']['count']++;
        $context['results']['nids_processed'][] = $entity->id();
        $context['message'] = t('Published quarterly report on @nid', ['@nid' => $entity->id()]);
      }
      else {
        $context['results']['nids_process_failed'][] = $entity->id();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('VBO Quartly report moderation', $e);
    }
  }

  /**
   * The processing finished function.
   *
   * @param object $success
   *   Success flag.
   * @param object $results
   *   Batch finished result.
   * @param object $operations
   *   Batch finished operations.
   */
  public static function batchFinished($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = $results['count'] . ' Quarterly report(s) has been published.';
      \Drupal::messenger()->addMessage($message);
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      if (count($results['nids_process_failed'])) {
        $message = t('An error occurred while processing on nid(s) %error_operation with arguments: @arguments', [
          '%error_operation' => implode(', ', $results['nids_process_failed']),
          '@arguments' => print_r($error_operation[1], TRUE),
        ]);
      }
      else {
        $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
          '%error_operation' => $error_operation[0],
          '@arguments' => print_r($error_operation[1], TRUE),
        ]);
      }
      \Drupal::messenger()->addError($message);
    }
  }

}
