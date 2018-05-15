<?php

namespace Drupal\gdpr_consent\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\gdpr_consent\Entity\InformBlock;
use Drupal\gdpr_consent\InformBlockInterface;

/**
 * Class GdprConsentController.
 *
 * @package Drupal\gdpr_consent\Controller
 */
class GdprConsentController extends ControllerBase {

  /**
   * The GDPR consent manager.
   *
   * @var \Drupal\gdpr_consent\GdprConsentManagerInterface
   */
  protected $gdprConsentManager;

  /**
   * Returns the GDPR consent manager service.
   *
   * @return \Drupal\gdpr_consent\GdprConsentManagerInterface
   *   The GDPR consent manager.
   */
  protected function gdprConsentManager() {
    if (!$this->gdprConsentManager) {
      $this->gdprConsentManager = \Drupal::service('gdpr_consent.manager');
    }
    return $this->gdprConsentManager;
  }

  /**
   * Show description of information block for the current page.
   *
   * @param string $informblock
   *   The 'informblock' entity ID.
   *
   * @return array
   *   The 'body' field value.
   */
  public function descriptionPage($informblock) {
    return ['#markup' => InformBlock::load($informblock)->body['value']];
  }

  /**
   * Title of an information block.
   *
   * @param string $informblock
   *   The 'informblock' entity ID.
   *
   * @return string
   *   The label of entity.
   */
  public function title($informblock) {
    return InformBlock::load($informblock)->label();
  }

  /**
   * Check if exist entity.
   *
   * @param string $informblock
   *   The 'informblock' entity ID.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function access($informblock) {
    if (InformBlock::load($informblock) instanceof InformBlockInterface) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
