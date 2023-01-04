<?php

namespace Drupal\foia_quarterly_data_report;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\workflows\Transition;
use Drupal\content_moderation\Entity\ContentModerationState;

/**
 * TargetStates, node moderation utilities and state status.
 */
class TargetStates {
  /**
   * Validation.
   *
   * @var content_moderationstate_transition_validation
   */
  protected $validation;

  /**
   * Current User.
   *
   * @var current_user
   */
  protected $currentUser;

  /**
   * Class construction.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   Moderation information service.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $validation
   *   Validation information service.
   * @param object $user
   *   Current user information.
   */
  public function __construct(ModerationInformationInterface $moderation_info, StateTransitionValidationInterface $validation, $user) {
    /**
     * The moderation information service:
     *   content_moderation.moderation_information.
     * @var \Drupal\content_moderation\ModerationInformationInterface
     */
    $this->moderationInfo = $moderation_info;
    /**
     * The moderation state transition validation service :
     * content_moderation.*state_transition_validation.
     * @var \Drupal\content_moderation\StateTransitionValidationInterface
     */
    $this->validation = $validation;
    $this->currentUser = $user;
  }

  /**
   * Class creation funtion.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container services.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation.state_transition_validation'),
      $container->get('current_user'),
    );
  }

  /**
   * Get node moderation target state.
   *
   * @param object $object
   *   Get the moderation object.
   */
  public function getTargetStates($object) {
    $result = [];
    // Get this node latest revision.
    $node = $this->getLatestRevision($object);
    $current_state = $node->moderation_state->value;
    $workflow = $this->moderationInfo->getWorkflowForEntity($node);

    /** @var \Drupal\workflows\Transition[] $transitions */
    $transitions = $this->validation->getValidTransitions($node, $this->currentUser);

    // Exclude self-transitions.
    $transitions = array_filter($transitions, function (Transition $transition) use ($current_state) {
      return $transition->to()->id() != $current_state;
    });
    $target_states = [];

    foreach ($transitions as $transition) {
      $target_states[$transition->to()->id()] = $transition->to()->label();
    }
    $result = [
      'workflow' => $workflow,
      'target_states' => $target_states,
      'current_state' => $current_state,
    ];
    return $result;
  }

  /**
   * Get node moderation state lable.
   *
   * @param object $node
   *   Get the moderation state label.
   */
  public function getModerationStateLabel($node) {
    $current_state = $node->moderation_state->value;
    $content_moderation_state = ContentModerationState::loadFromModeratedEntity($node);
    $wf = $content_moderation_state->get('workflow')->entity;
    $result = $wf->get('type_settings')['states'][$current_state]['label'];
    return $result;
  }

  /**
   * Get latest revision of node.
   *
   * @param object $object
   *   Get the latest revision.
   */
  public function getLatestRevision($object) {
    if (!isset($object)) {
      return NULL;
    }
    $vids = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($object);
    $node = \Drupal::entityTypeManager()->getStorage('node')->loadRevision(end($vids));
    return $node;
  }

}
