<?php

namespace Drupal\data_policy;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\data_policy\Entity\DataPolicyInterface;
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
      $links[$key] = Link::createFromRoute(strtolower($revision->getName()), 'data_policy.data_policy', ['id' => $revision->id()], [
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'title' => $revision->getName(),
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
  public function saveConsent($user_id, $state = UserConsentInterface::STATE_UNDECIDED, $action = NULL) {
    if ($state === TRUE) {
      $state = UserConsentInterface::STATE_AGREE;
    }
    elseif ($state === FALSE) {
      $state = UserConsentInterface::STATE_NOT_AGREE;
    }

    $entities = $this->getEntityIdsFromConsentText();
    $user_consents = $this->entityTypeManager->getStorage('user_consent')
      ->loadByProperties([
        'user_id' => $user_id,
        'status' => TRUE,
      ]);

    /** @var \Drupal\data_policy\DataPolicyStorageInterface $data_policy_storage */
    $data_policy_storage = $this->entityTypeManager->getStorage('data_policy');
    // Existing states for the current user.
    $existing_states = array_map(function (UserConsentInterface $user_consent) {
      return $user_consent->state->value;
    }, $user_consents);

    // This logic determines whether we need to create a new "user_consent"
    // entity or not, depending on whether there are new and active
    // "data_policy" with which the user should agree. Previously, there
    // was a `getState` method for this, but it is not relevant since now we
    // do not have a binding to only one entity.
    // See \Drupal\data_policy\Form\DataPolicyAgreement::submitForm.
    if ($action === 'submit') {
      $is_equals = TRUE;
      foreach ($existing_states as $existing_state) {
        if ($existing_state != $state) {
          $is_equals = FALSE;
          break;
        }
      }

      // If submitted states for user_consent entities are the same as
      // existing then we just need to do nothing.
      if ($is_equals) {
        return;
      }

      // Set an "unpublished" status for all "user_consent" entities that
      // were active before submit.
      if (!empty($user_consents)) {
        foreach ($user_consents as $user_consent) {
          $user_consent->setPublished(FALSE)->save();
        }
      }

      // Create new "user_consent" entities with active revision from
      // user consent text in the settings tab.
      foreach ($entities as $entity) {
        /** @var \Drupal\data_policy\Entity\DataPolicyInterface $data_policy */
        $data_policy = $data_policy_storage->load($entity);
        $this->createUserConsent($data_policy, $user_id, $state);
      }
    }
    // See \Drupal\data_policy\Form\DataPolicyAgreement::buildForm.
    elseif ($action === 'visit') {
      if (!empty($existing_states)) {
        // Existing revisions for the current user.
        $existing_revisions = array_map(function (UserConsentInterface $user_consent) {
          return $user_consent->data_policy_revision_id->value;
        }, $user_consents);
        $revisions = $this->getRevisionsByEntityIds($entities);
        $revision_ids_from_consent_text = array_map(function (DataPolicyInterface $revision) {
          return $revision->getRevisionId();
        }, $revisions);
        // If existing revisions for the current user are different from
        // current revisions (consent text in setting form) then we should
        // create "user_consent" entities with zero state and all entities
        // for the current user before visit the agreement page will be
        // removed from published.
        $diff = array_diff($existing_revisions, $revision_ids_from_consent_text);
        if (!empty($diff)) {
          foreach ($user_consents as $user_consent) {
            $user_consent->setPublished(FALSE)->save();
          }
          foreach ($entities as $entity) {
            /** @var \Drupal\data_policy\Entity\DataPolicyInterface $data_policy */
            $data_policy = $data_policy_storage->load($entity);
            $this->createUserConsent($data_policy, $user_id, $state);
          }
        }

        $is_equals = TRUE;
        $skip = TRUE;

        foreach ($existing_states as $existing_state) {
          // If the current state for the current user more then existing in
          // the database then we need to create new entries in the database.
          if ((int) $existing_state < (int) $state) {
            $skip = FALSE;
            break;
          }

          // If existing states are not equal the current user state then we
          // need to create new entries in the database.
          if ($existing_state != $state) {
            $is_equals = FALSE;
            break;
          }
        }

        if ($is_equals || $skip) {
          return;
        }
      }

      foreach ($entities as $entity) {
        /** @var \Drupal\data_policy\Entity\DataPolicyInterface $data_policy */
        $data_policy = $data_policy_storage->load($entity);
        $this->createUserConsent($data_policy, $user_id, $state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExistingUserConsents($user_id) {
    return $this->entityTypeManager
      ->getStorage('user_consent')
      ->getQuery()
      ->condition('status', 1)
      ->condition('user_id', $user_id)
      ->execute();
  }

  /**
   * Create the user_consent entity.
   *
   * @param \Drupal\data_policy\Entity\DataPolicyInterface $data_policy
   *   The data policy entity.
   * @param int $user_id
   *   The user id.
   * @param int $state
   *   The state for consent entity.
   */
  private function createUserConsent(DataPolicyInterface $data_policy, $user_id, $state) {
    $this->entityTypeManager->getStorage('user_consent')
      ->create()
      ->setRevision($data_policy)
      ->setOwnerId($user_id)
      ->set('state', $state)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function isDataPolicy() {
    return !empty($this->getEntityIdsFromConsentText());
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIdsFromConsentText() {
    $consent_text = $this->getConfig('consent_text');
    preg_match_all("#\[id:(\d+)\]#", $consent_text, $matches);

    return $matches[1];
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionsByEntityIds(array $entity_ids) {
    return $this->entityTypeManager->getStorage('data_policy')->loadMultiple($entity_ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig($name) {
    return $this->configFactory->get('data_policy.data_policy')->get($name);
  }

}
