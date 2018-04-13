<?php

namespace Drupal\gdpr_consent\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining User consent entities.
 *
 * @ingroup gdpr_consent
 */
interface UserConsentInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the User consent creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User consent.
   */
  public function getCreatedTime();

}
