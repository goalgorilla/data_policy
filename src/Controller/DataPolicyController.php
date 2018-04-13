<?php

namespace Drupal\gdpr_consent\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\gdpr_consent\Entity\DataPolicyInterface;

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
    $data_policy = $this->entityManager()->getStorage('data_policy')->loadRevision($data_policy_revision);
    $view_builder = $this->entityManager()->getViewBuilder('data_policy');

    return $view_builder->view($data_policy);
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
    $data_policy = $this->entityManager()->getStorage('data_policy')->loadRevision($data_policy_revision);
    return $this->t('Revision of %title from %date', ['%title' => $data_policy->label(), '%date' => format_date($data_policy->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Data policy .
   *
   * @param \Drupal\gdpr_consent\Entity\DataPolicyInterface $data_policy
   *   A Data policy  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(DataPolicyInterface $data_policy) {
    $account = $this->currentUser();
    $langcode = $data_policy->language()->getId();
    $langname = $data_policy->language()->getName();
    $languages = $data_policy->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $data_policy_storage = $this->entityManager()->getStorage('data_policy');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $data_policy->label()]) : $this->t('Revisions for %title', ['%title' => $data_policy->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all data policy revisions") || $account->hasPermission('administer data policy entities')));
    $delete_permission = (($account->hasPermission("delete all data policy revisions") || $account->hasPermission('administer data policy entities')));

    $rows = [];

    $vids = $data_policy_storage->revisionIds($data_policy);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\gdpr_consent\DataPolicyInterface $revision */
      $revision = $data_policy_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $data_policy->getRevisionId()) {
          $link = $this->l($date, new Url('entity.data_policy.revision', ['data_policy' => $data_policy->id(), 'data_policy_revision' => $vid]));
        }
        else {
          $link = $data_policy->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
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
              'url' => Url::fromRoute('entity.data_policy.revision_delete', ['data_policy' => $data_policy->id(), 'data_policy_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['data_policy_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
