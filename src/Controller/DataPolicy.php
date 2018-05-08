<?php

namespace Drupal\gdpr_consent\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\gdpr_consent\Entity\UserConsentInterface;

/**
 * Class DataPolicy.
 *
 *  Returns responses for Data policy routes.
 */
class DataPolicy extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The GDPR consent manager.
   *
   * @var \Drupal\gdpr_consent\GdprConsentManagerInterface
   */
  protected $gdprConsentManager;

  /**
   * Retrieves the date formatter.
   *
   * @return \Drupal\Core\Datetime\DateFormatter
   *   The date formatter.
   */
  protected function dateFormatter() {
    if (!isset($this->dateFormatter)) {
      $this->dateFormatter = \Drupal::service('date.formatter');
    }
    return $this->dateFormatter;
  }

  /**
   * Retrieves the renderer.
   *
   * @return \Drupal\Core\Render\Renderer
   *   The renderer.
   */
  protected function renderer() {
    if (!isset($this->renderer)) {
      $this->renderer = \Drupal::service('renderer');
    }
    return $this->renderer;
  }

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
   * Show description of data policy.
   *
   * @return array
   *   The data policy description text.
   */
  public function entityOverviewPage() {
    $entity_id = $this->gdprConsentManager()->getConfig('entity_id');

    if (!empty($entity_id)) {
      $description = $this->entityTypeManager()->getStorage('data_policy')
        ->load($entity_id)
        ->field_description
        ->value;

      $description = Markup::create($description);
    }
    else {
      $description = $this->t('Data policy is not created.');
    }

    return [
      '#theme' => 'gdpr_consent_data_policy',
      '#content' => $description,
    ];
  }

  /**
   * Check if data policy is created.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function entityOverviewAccess() {
    if ($this->gdprConsentManager()->isDataPolicy()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Displays a Data policy revision.
   *
   * @param int $data_policy_revision
   *   The Data policy  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionOverviewPage($data_policy_revision) {
    $build['data_policy'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Revision data'),
    ];

    $data_policy = $this->entityTypeManager()->getStorage('data_policy')
      ->loadRevision($data_policy_revision);

    $view_builder = $this->entityTypeManager()->getViewBuilder('data_policy');

    $build['data_policy']['revision'] = $view_builder->view($data_policy);

    $user_consents = $this->entityTypeManager()->getStorage('user_consent')
      ->loadByProperties([
        'data_policy_revision_id' => $data_policy_revision,
      ]);

    if (!empty($user_consents)) {
      $build['user_consent'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('User consents for current revision'),
      ];

      $build['user_consent']['list'] = [
        '#theme' => 'table',
        '#header' => [
          $this->t('User'),
          $this->t('State'),
          $this->t('Date and time'),
        ],
      ];

      $states = [
        UserConsentInterface::STATE_UNDECIDED => $this->t('Undecided'),
        UserConsentInterface::STATE_NOT_AGREE => $this->t('Not agree'),
        UserConsentInterface::STATE_AGREE => $this->t('Agree'),
      ];

      /** @var \Drupal\gdpr_consent\Entity\UserConsentInterface $user_consent */
      foreach ($user_consents as $user_consent) {
        $build['user_consent']['list']['#rows'][] = [
          $user_consent->getOwner()->getDisplayName(),
          $states[$user_consent->state->value],
          $this->dateFormatter()->format($user_consent->getChangedTime(), 'short'),
        ];
      }
    }

    return $build;
  }

  /**
   * Page title callback for a Data policy revision.
   *
   * @param int $data_policy_revision
   *   The Data policy  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionOverviewTitle($data_policy_revision) {
    $data_policy = $this->entityTypeManager()->getStorage('data_policy')
      ->loadRevision($data_policy_revision);

    return $this->t('Data policy revision from %date', [
      '%date' => $this->dateFormatter()->format($data_policy->getRevisionCreationTime()),
    ]);
  }

  /**
   * Generates an overview table of older revisions of a Data policy.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionsOverviewPage() {
    $build = [
      'data_policy_revisions_table' => [
        '#theme' => 'table',
        '#header' => [
          $this->t('Revision'),
          $this->t('Operations'),
        ],
        '#rows' => [],
        '#empty' => $this->t('List is empty.'),
      ],
    ];

    $entity_id = $this->config('gdpr_consent.data_policy')->get('entity_id');

    if (empty($entity_id)) {
      return $build;
    }

    /** @var \Drupal\gdpr_consent\DataPolicyStorageInterface $data_policy_storage */
    $data_policy_storage = $this->entityTypeManager()->getStorage('data_policy');

    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $data_policy */
    $data_policy = $data_policy_storage->load($entity_id);

    $account = $this->currentUser();
    $langcode = $data_policy->language()->getId();
    $languages = $data_policy->getTranslationLanguages();
    $has_translations = count($languages) > 1;

    $revert_permission = $account->hasPermission('revert all data policy revisions') || $account->hasPermission('administer data policy entities');
    $delete_permission = $account->hasPermission('delete all data policy revisions') || $account->hasPermission('administer data policy entities');

    $vids = $data_policy_storage->revisionIds($data_policy);

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $revision */
      $revision = $data_policy_storage->loadRevision($vid);

      // Only show revisions that are affected by the language that is being
      // displayed.
      if (!$revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        continue;
      }

      $username = [
        '#theme' => 'username',
        '#account' => $revision->getRevisionUser(),
      ];

      // Use revision link to link to revisions that are not active.
      $date = $this->dateFormatter()
        ->format($revision->getRevisionCreationTime(), 'short');

      $row = [];

      $column = [
        'data' => [
          '#theme' => 'gdpr_consent_data_policy_revision',
          '#date' => $date,
          '#username' => $this->renderer()->renderPlain($username),
          '#current' => $revision->isDefaultRevision(),
          '#message' => [
            '#markup' => Unicode::truncate($revision->getRevisionLogMessage(), 80, TRUE, TRUE),
            '#allowed_tags' => Xss::getHtmlTagList(),
          ],
        ],
      ];

      $row[] = $column;

      $links = [];

      $links['view'] = [
        'title' => $this->t('View'),
        'url' => Url::fromRoute('entity.data_policy.revision', [
          'data_policy' => $data_policy->id(),
          'data_policy_revision' => $vid,
        ]),
      ];

      if (!$revision->isDefaultRevision()) {
        if ($revert_permission) {
          $links['revert'] = [
            'title' => $this->t('Revert'),
            'url' => $has_translations ?
            Url::fromRoute('entity.data_policy.translation_revert', [
              'data_policy' => $data_policy->id(),
              'data_policy_revision' => $vid,
              'langcode' => $langcode,
            ]) :
            Url::fromRoute('entity.data_policy.revision_revert', [
              'data_policy' => $data_policy->id(),
              'data_policy_revision' => $vid,
            ]),
          ];
        }

        if ($delete_permission) {
          $links['delete'] = [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('entity.data_policy.revision_delete', [
              'data_policy' => $data_policy->id(),
              'data_policy_revision' => $vid,
            ]),
          ];
        }
      }

      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];

      if ($revision->isDefaultRevision()) {
        foreach ($row as &$current) {
          $current['class'] = ['revision-current'];
        }
      }

      $build['data_policy_revisions_table']['#rows'][] = $row;
    }

    return $build;
  }

  /**
   * Check access to agreement page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Allow to open page when a user was not give consent on a current version
   *   of data policy.
   */
  public function agreementAccess() {
    if ($this->gdprConsentManager()->needConsent()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}