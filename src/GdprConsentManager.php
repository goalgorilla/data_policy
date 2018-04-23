<?php

namespace Drupal\gdpr_consent;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Defines the GDPR Consent Manager service.
 */
class GdprConsentManager implements GdprConsentManagerInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new GDPR Consent Manager service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function needConsent() {
    if ($this->currentUser->isAnonymous() || $this->currentUser->id() == 1) {
      return FALSE;
    }

    $config = $this->configFactory->get('gdpr_consent.data_policy');

    if (empty($config->get('enforce_consent'))) {
      return FALSE;
    }

    $entity_id = $config->get('entity_id');

    /** @var \Drupal\gdpr_consent\DataPolicyStorageInterface $data_policy_storage */
    $data_policy_storage = $this->entityTypeManager->getStorage('data_policy');

    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $data_policy */
    $data_policy = $data_policy_storage->load($entity_id);

    $vids = $data_policy_storage->revisionIds($data_policy);

    $vid = end($vids);

    $user_consents = $this->entityTypeManager->getStorage('user_consent')
      ->loadByProperties([
        'user_id' => $this->currentUser->id(),
        'data_policy_revision_id' => $vid,
      ]);

    if (!empty($user_consents)) {
      return FALSE;
    }

    return TRUE;
  }

}
