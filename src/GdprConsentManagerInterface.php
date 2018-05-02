<?php

namespace Drupal\gdpr_consent;

use Drupal\gdpr_consent\Entity\UserConsentInterface;

/**
 * Defines the GDPR Consent Manager service interface.
 */
interface GdprConsentManagerInterface {

  /**
   * Check if user gave consent on a current version of data policy.
   *
   * @return bool
   *   TRUE if consent is needed.
   */
  public function needConsent();

  /**
   * Add checkbox to form which allow user give consent on data policy.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  public function addCheckbox(array &$form);

  /**
   * Save user consent.
   *
   * @param int $user_id
   *   The user ID.
   * @param mixed $state
   *   The one of three allowed states:
   *   - undecided,
   *   - not agree,
   *   - agree.
   */
  public function saveConsent($user_id, $state = UserConsentInterface::STATE_UNDECIED);

}
