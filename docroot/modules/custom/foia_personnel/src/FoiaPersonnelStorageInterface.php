<?php

namespace Drupal\foia_personnel;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\foia_personnel\Entity\FoiaPersonnelInterface;

/**
 * Defines the storage handler class for FOIA Personnel entities.
 *
 * This extends the base storage class, adding required special handling for
 * FOIA Personnel entities.
 *
 * @ingroup foia_personnel
 */
interface FoiaPersonnelStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of FOIA Personnel revision IDs for a specific FOIA Personnel.
   *
   * @param \Drupal\foia_personnel\Entity\FoiaPersonnelInterface $entity
   *   The FOIA Personnel entity.
   *
   * @return int[]
   *   FOIA Personnel revision IDs (in ascending order).
   */
  public function revisionIds(FoiaPersonnelInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as FOIA Personnel author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   FOIA Personnel revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\foia_personnel\Entity\FoiaPersonnelInterface $entity
   *   The FOIA Personnel entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(FoiaPersonnelInterface $entity);

  /**
   * Unsets the language for all FOIA Personnel with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
