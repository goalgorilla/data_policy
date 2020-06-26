<?php

namespace Drupal\data_policy;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\data_policy\Entity\UserConsent;
use Drupal\data_policy\Entity\UserConsentInterface;

/**
 * Defines the Data Policy Consent Manager service.
 */
class DataPolicyConsentManager implements DataPolicyConsentManagerInterface {

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
   * The data policy entity.
   *
   * @var \Drupal\data_policy\Entity\DataPolicyInterface
   */
  protected $entity;

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
    return $this->isDataPolicy() && !$this->currentUser->hasPermission('without consent');
  }

  /**
   * {@inheritdoc}
   */
  public function addCheckbox(array &$form) {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $entity_ids = $this->getEntityIdsFromConsentText();
    $revisions = $this->getRevisionsByEntityIds($entity_ids);
    $links = [];

    foreach ($revisions as $key => $revision) {
      $name = $revision->getName() ?: $this->t('Data policy');
      $links[$key] = Link::createFromRoute(strtolower($name), 'data_policy.data_policy', ['id' => $revision->id()], [
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'title' => $name,
            'width' => 700,
            'height' => 700,
          ]),
        ],
      ]);
    }

    $enforce_consent = !empty($this->getConfig('enforce_consent'));
    $enforce_consent_text = $this->getConfig('consent_text');

    foreach ($links as $entity_id => $link) {
      $enforce_consent_text = str_replace("[id:{$entity_id}]", $link->toString(), $enforce_consent_text);
    }

    $form['data_policy'] = [
      '#type' => 'checkbox',
      '#title' => $enforce_consent_text,
      '#required' => $enforce_consent && $this->currentUser->isAnonymous(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function saveConsent($user_id, $state = UserConsentInterface::STATE_UNDECIDED) {
    if ($state === TRUE) {
      $state = UserConsentInterface::STATE_AGREE;
    }
    elseif ($state === FALSE) {
      $state = UserConsentInterface::STATE_NOT_AGREE;
    }

    $last_states = $this->getStates();

    $return = [];
    if ($last_states !== FALSE) {
      foreach ($last_states as $key => $last_state) {
        if ($last_state == $state) {
          $return[$key] = TRUE;
        }
        else {
          if (!empty($this->getConfig('enforce_consent'))) {
            // Allow switching to state with higher priority (from "undecided" to
            // "no agree").
            if ($last_state > $state) {
              $return[$key] = TRUE;
            }
          }
          else {
            // Allow all switching cases without cases when switchbacking to the
            // "undecided" state (from "not agree" to "undecided" or from "agree"
            // to "undecided").
            if ($last_state != UserConsentInterface::STATE_UNDECIDED && $state == UserConsentInterface::STATE_UNDECIDED) {
              $return[$key] = TRUE;
            }
          }
        }
      }
    }

    if (in_array(TRUE, $return)) {
      return;
    }

    $entities = $this->getEntityIdsFromConsentText();
    $revisions = $this->getRevisionsByEntityIds($entities);
    $user_consents = $this->entityTypeManager->getStorage('user_consent')
      ->loadByProperties([
        'user_id' => $user_id,
        'status' => TRUE,
        'data_policy_revision_id' => array_map(function ($revision){return $revision->vid->value;}, $revisions),
      ]);

    if (!empty($user_consents)) {
      foreach ($user_consents as $user_consent) {
        $user_consent->setPublished(FALSE)->save();
      }
    }

    /** @var \Drupal\data_policy\DataPolicyStorageInterface $data_policy_storage */
    $data_policy_storage = $this->entityTypeManager->getStorage('data_policy');
    foreach ($entities as $entity) {
      /** @var \Drupal\data_policy\Entity\DataPolicy $data_policy */
      $data_policy = $data_policy_storage->load($entity);

      UserConsent::create()->setRevision($data_policy)
        ->setOwnerId($user_id)
        ->set('state', $state)
        ->save();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function isDataPolicy() {
    return !empty($this->getEntityIdsFromConsentText());
  }

  /**
   * Return states of last consent for entities of the current user.
   *
   * @return array|false
   *   The state numbers or FALSE if consents are absent.
   */
  protected function getStates() {
    /** @var \Drupal\data_policy\DataPolicyStorageInterface $data_policy_storage */
    $data_policy_storage = $this->entityTypeManager->getStorage('data_policy');
    $entity_ids = $this->getEntityIdsFromConsentText();

    if (empty($entity_ids)) {
      $entity_ids[] = $data_policy_storage->load($this->getConfig('entity_id'));
    }

    $revisions = $this->getRevisionsByEntityIds($entity_ids);
    $revision_ids = array_map(function ($revision) { return $revision->vid->value; }, $revisions);

    $user_consents = [];
    foreach ($revision_ids as $entity_id => $revision_id) {
      $user_consents[$entity_id] = $this->entityTypeManager->getStorage('user_consent')
        ->loadByProperties([
          'user_id' => $this->currentUser->id(),
          'data_policy_revision_id' => $revision_id,
        ]);
    }

    if (!empty($user_consents)) {
      $states = [];
      foreach ($user_consents as $entity_id => $user_consent) {
        if (is_array($user_consent)) {
          $states[$entity_id] = end($user_consent)->state->value;
        }
      }
      return $states;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIdsFromConsentText() {
    $consent_text = $this->getConfig('consent_text');
    preg_match_all("#\[(id:\d+)\]#", $consent_text, $matches, PREG_PATTERN_ORDER);
    list($search, $entity_ids) = $matches;

    foreach ($entity_ids as $key => $entity_id) {
      $entity_ids[$key] = str_replace('id:', '', $entity_id);
    }

    return $entity_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionsByEntityIds($entity_ids) {
    $revisions = [];
    foreach ($entity_ids as $entity_id) {
      /** @var \Drupal\data_policy\DataPolicyStorageInterface $data_policy_storage */
      $data_policy_storage = $this->entityTypeManager->getStorage('data_policy');

      $this->entity = $data_policy_storage->load($entity_id);
      $vids = $data_policy_storage->revisionIds($this->entity);

      foreach ($vids as $vid) {
        $revisions[$entity_id] = $data_policy_storage->loadRevision($vid);
        if ($data_policy_storage->loadRevision($vid)->isDefaultRevision()) {
          break;
        }
      }
    }

    return $revisions;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($name) {
    return $this->configFactory->get('data_policy.data_policy')->get($name);
  }

}
