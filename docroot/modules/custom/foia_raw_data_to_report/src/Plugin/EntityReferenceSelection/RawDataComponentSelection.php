<?php

namespace Drupal\foia_raw_data_to_report\Plugin\EntityReferenceSelection;

use Drupal\node\Plugin\EntityReferenceSelection\NodeSelection;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Limits component choices to the parent raw data report's agency.
 *
 * @EntityReferenceSelection(
 *   id = "raw_data_component",
 *   label = @Translation("Raw data report components"),
 *   entity_types = {"node"},
 *   group = "raw_data_component",
 *   weight = 0
 * )
 */
class RawDataComponentSelection extends NodeSelection {

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    if (!$ids) {
      return [];
    }
    // Paragraphs resolves its parent from storage, so it cannot supply the
    // submitted agency before the report's first save or after an agency edit.
    // Preserve core reference/access checks here. RawDataUploads validates
    // agency membership against the submitted parent report instead.
    return parent::buildEntityQuery()
      ->condition('type', 'agency_component')
      ->condition('nid', $ids, 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    $configuration = $this->getConfiguration();
    $agency = $configuration['agency_id'] ?? NULL;
    $entity = $configuration['entity'] ?? NULL;
    if (!array_key_exists('agency_id', $configuration) && $entity instanceof ParagraphInterface) {
      $parent = $entity->getParentEntity();
      if ($parent && $parent->bundle() === 'raw_data_to_report') {
        $agency = $parent->get('field_agency')->target_id;
      }
    }
    $query->condition('type', 'agency_component');
    $query->condition('field_agency.target_id', $agency ?: 0);
    return $query;
  }

}
