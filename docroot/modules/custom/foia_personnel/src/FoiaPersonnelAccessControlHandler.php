<?php

namespace Drupal\foia_personnel;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the FOIA Personnel entity.
 *
 * @see \Drupal\foia_personnel\Entity\FoiaPersonnel.
 */
class FoiaPersonnelAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\foia_personnel\Entity\FoiaPersonnelInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished foia personnel entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published foia personnel entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit foia personnel entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete foia personnel entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add foia personnel entities');
  }

}
