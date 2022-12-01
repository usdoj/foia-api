<?php

namespace Drupal\webform_template\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.webform_ui.element')) {
      $route->setRequirement('_custom_access', 'Drupal\webform_template\Access\WebformTemplateAccess::access');
    }
    if ($route = $collection->get('entity.webform_ui.element.add_page')) {
      $route->setRequirement('_custom_access', 'Drupal\webform_template\Access\WebformTemplateAccess::access');
    }
    if ($route = $collection->get('entity.webform_ui.element.add_layout')) {
      $route->setRequirement('_custom_access', 'Drupal\webform_template\Access\WebformTemplateAccess::access');
    }
    if ($route = $collection->get('entity.webform_ui.element.delete_form')) {
      $route->setRequirement('_custom_access', 'Drupal\webform_template\Access\WebformTemplateAccess::access');
    }
    if ($route = $collection->get('entity.webform_ui.element.edit_form')) {
      $route->setRequirement('_custom_access', 'Drupal\webform_template\Access\WebformTemplateAccess::access');
    }
  }

}
