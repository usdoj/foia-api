<?php

namespace Drupal\foia_personnel;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\foia_personnel\Entity\FoiaPersonnelInterface;

/**
 * Defines the storage handler class for FOIA Personnel entities.
 *
 * This extends the base storage class, adding required special handling for
 * FOIA Personnel entities.
 *
 * @ingroup foia_personnel
 */
class FoiaPersonnelStorage extends SqlContentEntityStorage implements FoiaPersonnelStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(FoiaPersonnelInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {foia_personnel_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {foia_personnel_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(FoiaPersonnelInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {foia_personnel_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('foia_personnel_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
