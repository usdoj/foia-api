<?php

namespace Drupal\foia_request;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of FOIA Request entities.
 *
 * @ingroup foia_request
 */
class FoiaRequestListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('FOIA Request ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\foia_request\Entity\FoiaRequest */
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

}
