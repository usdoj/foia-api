<?php

namespace Drupal\foia_webform;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class AgencyLookupService.
 */
class AgencyLookupService implements AgencyLookupServiceInterface {

  /**
   * {@inheritdoc}
   */
  public function getComponentFromWebform($webformId) {
    $entityQueryService = \Drupal::service('entity.query');
    $query = $entityQueryService->get('node')
      ->condition('type', 'agency_component')
      ->condition('field_request_submission_form', $webformId);
    $nid = $query->execute();

    return ($nid) ? Node::load(reset($nid)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAgencyFromComponent(NodeInterface $agencyComponent) {
    return $agencyComponent->get('field_agency')->getEntity();
  }

}
