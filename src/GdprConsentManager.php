<?php

namespace Drupal\gdpr_consent;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\gdpr_consent\Entity\DataPolicy;
use Drupal\gdpr_consent\Entity\UserConsent;
use Drupal\gdpr_consent\Entity\UserConsentInterface;

/**
 * Defines the GDPR Consent Manager service.
 */
class GdprConsentManager implements GdprConsentManagerInterface {

  use StringTranslationTrait;

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
    if ($this->currentUser->hasPermission('without consent')) {
      return FALSE;
    }

    /** @var \Drupal\gdpr_consent\DataPolicyStorageInterface $data_policy_storage */
    $data_policy_storage = $this->entityTypeManager->getStorage('data_policy');

    $entity_id = $this->configFactory->get('gdpr_consent.data_policy')
      ->get('entity_id');

    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $data_policy */
    $data_policy = $data_policy_storage->load($entity_id);

    $vids = $data_policy_storage->revisionIds($data_policy);

    $vid = end($vids);

    $user_consents = $this->entityTypeManager->getStorage('user_consent')
      ->loadByProperties([
        'user_id' => $this->currentUser->id(),
        'data_policy_revision_id' => $vid,
        'status' => TRUE,
      ]);

    return empty($user_consents);
  }

  /**
   * {@inheritdoc}
   */
  public function addCheckbox(array &$form) {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $link = Link::createFromRoute($this->t('data policy'), 'gdpr_consent.data_policy', [], [
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'title' => t('Data policy'),
          'width' => 700,
          'height' => 700,
        ]),
      ],
    ]);

    $enforce_consent = $this->configFactory->get('gdpr_consent.data_policy')
      ->get('enforce_consent');

    $form['data_policy'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('I agree with the @url', [
        '@url' => $link->toString(),
      ]),
      '#required' => !empty($enforce_consent) && $this->currentUser->isAnonymous(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function saveConsent($user_id, $state = UserConsentInterface::STATE_UNDECIED) {
    $entity_id = $this->configFactory->get('gdpr_consent.data_policy')
      ->get('entity_id');

    if ($state === TRUE) {
      $state = UserConsentInterface::STATE_AGRRE;
    }
    elseif ($state === FALSE) {
      $state = UserConsentInterface::STATE_NOT_AGREE;
    }

    UserConsent::create()->setRevision(DataPolicy::load($entity_id))
      ->setOwnerId($user_id)
      ->set('state', $state)
      ->save();
  }

}
