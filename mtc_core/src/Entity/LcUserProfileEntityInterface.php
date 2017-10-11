<?php

namespace Drupal\mtc_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining User profile entity entities.
 *
 * @ingroup mtc_core
 */
interface LcUserProfileEntityInterface extends  ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the User profile entity title.
   *
   * @return string
   *   Title of the User profile entity.
   */
  public function getTitle();

  /**
   * Sets the User profile entity title.
   *
   * @param string $name
   *   The User profile entity title.
   *
   * @return \Drupal\mtc_core\Entity\UserProfileEntityInterface
   *   The called User profile entity entity.
   */
  public function setTitle($title);

  /**
   * Gets the User profile entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User profile entity.
   */
  public function getCreatedTime();

  /**
   * Sets the User profile entity creation timestamp.
   *
   * @param int $timestamp
   *   The User profile entity creation timestamp.
   *
   * @return \Drupal\mtc_core\Entity\UserProfileEntityInterface
   *   The called User profile entity entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the User profile entity published status indicator.
   *
   * Unpublished User profile entity are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the User profile entity is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a User profile entity.
   *
   * @param bool $published
   *   TRUE to set this User profile entity to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\mtc_core\Entity\UserProfileEntityInterface
   *   The called User profile entity entity.
   */
  public function setPublished($published);

}
