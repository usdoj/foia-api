<?php

namespace Drupal\webform_template\Access;

use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Defines the custom access control handler for the webform_template module.
 */
class WebformTemplateAccess {

  /**
   * Check that webform can be updated by a user.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function access(WebformInterface $webform, AccountInterface $account) {
    $templateController = \Drupal::service('webform_template.template_controller');
    $templated = $templateController->getTemplateConfiguration($webform->id());
    if ($templated) {
      return AccessResult::forbidden();
    }
    else {
      return $webform->access('update', $account, TRUE);
    }
  }

}
