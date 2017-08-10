<?php

namespace Drupal\foia_personnel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of FOIA Personnel entities.
 *
 * @ingroup foia_personnel
 */
class FoiaPersonnelListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('FOIA Personnel ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\foia_personnel\Entity\FoiaPersonnel */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.foia_personnel.edit_form',
      ['foia_personnel' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
