<?php
/**
 * @file
 * Contains \Drupal\node_to_docx\Access\NodeToDocxAccessCheck.
 */
namespace Drupal\node_to_docx\Access;

use Drupal\Core\Access\AccessCheckInterface as AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

class NodeToDocxAccessCheck implements AccessCheckInterface {

  /**
   *  Returns the machine name.
   *
   *  @param \Symfony\Component\Routing\Route $route
   *   The route to be checked.
   *  @return string
   */
  public function applies(Route $route) {
    return '_access_check_node_to_docx';
  }

  /**
   *  Checks access for the account and route using the custom access checker.
   *
   *  @param \Symfony\Component\Routing\Route $route
   *   The route to be checked.
   *  @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object to be checked.
   *  @param \Drupal\Core\Session\AccountInterface $account
   *   The account being checked.
   *
   *  @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // check permissions if path is "convert-to-docx"
    if ($route_match->getRouteName() == 'node_to_docx.convert') {
      $node = $route_match->getParameters()->get('node');
      if ($account->hasPermission('generate docx using node to docx')) {
        return AccessResult::allowed();
      }
      if (!$account->hasPermission('generate docx using node to docx: ' . $node->getType())) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::allowed();
  }
}