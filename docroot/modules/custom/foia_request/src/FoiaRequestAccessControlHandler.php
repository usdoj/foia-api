<?php

namespace Drupal\foia_request;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the FOIA Request entity.
 *
 * @see \Drupal\foia_request\Entity\FoiaRequest.
 */
class FoiaRequestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\foia_request\Entity\FoiaRequestInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view foia request entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit foia request entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete foia request entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add foia request entities');
  }

}
