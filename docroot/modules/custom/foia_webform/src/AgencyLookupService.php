<?php

namespace Drupal\foia_webform;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Class AgencyLookupService lookup queries.
 *
 * For agency_component and field_agency.
 *
 * @package Drupal\foia_webform;
 */
class AgencyLookupService implements AgencyLookupServiceInterface {

  /**
   * The entity query service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentFromWebform($webformId) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'agency_component')
      ->condition('field_request_submission_form', $webformId)
      ->condition('status', NodeInterface::PUBLISHED);
    $nid = $query->execute();

    return ($nid) ? Node::load(reset($nid)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAgencyFromComponent(NodeInterface $agencyComponent) {
    $agencyTerm = NULL;
    if (!$agencyComponent->get('field_agency')->isEmpty()) {
      $agencyTermId = $agencyComponent->get('field_agency')->target_id;
      $agencyTerm = Term::load($agencyTermId);
    }
    return $agencyTerm;
  }

}
