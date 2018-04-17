<?php

namespace Drupal\gdpr_consent\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;

/**
 * Class DataPolicyController.
 *
 *  Returns responses for Data policy routes.
 */
class DataPolicyController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Data policy  revision.
   *
   * @param int $data_policy_revision
   *   The Data policy  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($data_policy_revision) {
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
          $this->t('Date and time'),
        ],
      ];

      /** @var \Drupal\gdpr_consent\Entity\UserConsentInterface $user_consent */
      foreach ($user_consents as $user_consent) {
        $build['user_consent']['list']['#rows'][] = [
          $user_consent->getOwner()->getDisplayName(),
          \Drupal::service('date.formatter')->format($user_consent->getChangedTime(), 'short'),
        ];
      }
    }

    return $build;
  }

  /**
   * Page title callback for a Data policy  revision.
   *
   * @param int $data_policy_revision
   *   The Data policy  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($data_policy_revision) {
    $data_policy = $this->entityTypeManager()->getStorage('data_policy')
      ->loadRevision($data_policy_revision);

    $formatter = \Drupal::service('date.formatter');

    return $this->t('Data policy revision from %date', [
      '%date' => $formatter->format($data_policy->getRevisionCreationTime()),
    ]);
  }

  /**
   * Check if reverting is allowed.
   *
   * @return bool
   *   TRUE if reverting is allowed.
   */
  public function allowRevert() {
    $permissions = [
      'revert all data policy revisions',
      'administer data policy entities',
    ];

    foreach ($permissions as $permission) {
      if ($this->currentUser()->hasPermission($permission)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Generates an overview table of older revisions of a Data policy.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview() {
    $entity_id = $this->config('gdpr_consent.data_policy')->get('entity_id');

    /** @var \Drupal\gdpr_consent\DataPolicyStorageInterface $data_policy_storage */
    $data_policy_storage = $this->entityTypeManager()->getStorage('data_policy');

    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $data_policy */
    $data_policy = $data_policy_storage->load($entity_id);

    $account = $this->currentUser();
    $langcode = $data_policy->language()->getId();
    $languages = $data_policy->getTranslationLanguages();
    $has_translations = count($languages) > 1;

    $revert_permission = $this->allowRevert();
    $delete_permission = $account->hasPermission('delete all data policy revisions') || $account->hasPermission('administer data policy entities');

    $rows = [];

    $vids = $data_policy_storage->revisionIds($data_policy);

    $latest_revision = TRUE;

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
      $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');

      if ($vid != $data_policy->getRevisionId()) {
        $url = new Url('entity.data_policy.revision', [
          'data_policy' => $data_policy->id(),
          'data_policy_revision' => $vid,
        ]);

        $link = $this->getLinkGenerator()->generate($date, $url);
      }
      else {
        $link = $data_policy->toLink($date)->toString();
      }

      $row = [];

      $column = [
        'data' => [
          '#type' => 'inline_template',
          '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if current %} <em>({% trans %}current revision{% endtrans %})</em>{% endif %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
          '#context' => [
            'date' => $link,
            'username' => \Drupal::service('renderer')->renderPlain($username),
            'current' => $latest_revision,
            'message' => [
              '#markup' => Unicode::truncate($revision->getRevisionLogMessage(), 80, TRUE, TRUE),
              '#allowed_tags' => Xss::getHtmlTagList(),
            ],
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

      if (!$latest_revision) {
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

      if ($latest_revision) {
        foreach ($row as &$current) {
          $current['class'] = ['revision-current'];
        }

        $latest_revision = FALSE;
      }

      $rows[] = $row;
    }

    $build['data_policy_revisions_table'] = [
      '#theme' => 'table',
      '#header' => [
        $this->t('Revision'),
        $this->t('Operations'),
      ],
      '#rows' => $rows,
    ];

    return $build;
  }

}
