<?php

namespace Drupal\foia_personnel\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining FOIA Personnel entities.
 *
 * @ingroup foia_personnel
 */
interface FoiaPersonnelInterface extends RevisionableInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the FOIA Personnel name.
   *
   * @return string
   *   Name of the FOIA Personnel.
   */
  public function getName();

  /**
   * Sets the FOIA Personnel name.
   *
   * @param string $name
   *   The FOIA Personnel name.
   *
   * @return \Drupal\foia_personnel\Entity\FoiaPersonnelInterface
   *   The called FOIA Personnel entity.
   */
  public function setName($name);

  /**
   * Gets the FOIA Personnel creation timestamp.
   *
   * @return int
   *   Creation timestamp of the FOIA Personnel.
   */
  public function getCreatedTime();

  /**
   * Sets the FOIA Personnel creation timestamp.
   *
   * @param int $timestamp
   *   The FOIA Personnel creation timestamp.
   *
   * @return \Drupal\foia_personnel\Entity\FoiaPersonnelInterface
   *   The called FOIA Personnel entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the FOIA Personnel published status indicator.
   *
   * Unpublished FOIA Personnel are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the FOIA Personnel is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a FOIA Personnel.
   *
   * @param bool $published
   *   TRUE to set this FOIA Personnel to published, FALSE to set it to
   *   unpublished.
   *
   * @return \Drupal\foia_personnel\Entity\FoiaPersonnelInterface
   *   The called FOIA Personnel entity.
   */
  public function setPublished($published);

  /**
   * Gets the FOIA Personnel revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the FOIA Personnel revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\foia_personnel\Entity\FoiaPersonnelInterface
   *   The called FOIA Personnel entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the FOIA Personnel revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the FOIA Personnel revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\foia_personnel\Entity\FoiaPersonnelInterface
   *   The called FOIA Personnel entity.
   */
  public function setRevisionUserId($uid);

}
