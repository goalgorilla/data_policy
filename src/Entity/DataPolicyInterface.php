<?php

namespace Drupal\gdpr_consent\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Data policy entities.
 *
 * @ingroup gdpr_consent
 */
interface DataPolicyInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Data policy creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Data policy.
   */
  public function getCreatedTime();

}
