<?php

namespace Drupal\gdpr_consent;

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

}
