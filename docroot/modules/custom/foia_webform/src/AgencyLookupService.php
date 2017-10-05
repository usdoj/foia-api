<?php

namespace Drupal\foia_webform;

use Drupal\node\Entity\Node;

/**
 * Class AgencyLookupService.
 *
 * @package Drupal\foia_webform
 */
class AgencyLookupService implements AgencyLookupServiceInterface {

  /**
   * Queries for an associated Agency Component given a form ID.
   *
   * @param string $webformId
   *   The form ID.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The Agency Component object or NULL.
   */
  public function getComponentByWebform($webformId) {
    $entity_query_service = \Drupal::service('entity.query');
    $query = $entity_query_service->get('node')
      ->condition('type', 'agency_component')
      ->condition('field_request_submission_form', $webformId);
    $nid = $query->execute();

    $node = ($nid) ? Node::load(reset($nid)) : NULL;
    return $node;
  }

}
