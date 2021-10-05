<?php

namespace Drupal\foia_personnel\Controller;

use Drupal\Core\Link;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\foia_personnel\Entity\FoiaPersonnelInterface;

/**
 * Class FoiaPersonnelController.
 *
 *  Returns responses for FOIA Personnel routes.
 */
class FoiaPersonnelController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a FOIA Personnel  revision.
   *
   * @param int $foia_personnel_revision
   *   The FOIA Personnel  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($foia_personnel_revision) {
    $foia_personnel = \Drupal::service('entity_type.manager')->getStorage('foia_personnel')->loadRevision($foia_personnel_revision);
    $view_builder = \Drupal::service('entity_type.manager')->getViewBuilder('foia_personnel');

    return $view_builder->view($foia_personnel);
  }

  /**
   * Page title callback for a FOIA Personnel  revision.
   *
   * @param int $foia_personnel_revision
   *   The FOIA Personnel  revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($foia_personnel_revision) {
    $foia_personnel = \Drupal::service('entity_type.manager')->getStorage('foia_personnel')->loadRevision($foia_personnel_revision);
    return $this->t('Revision of %title from %date', ['%title' => $foia_personnel->label(), '%date' => \Drupal::service('date.formatter')->format($foia_personnel->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a FOIA Personnel .
   *
   * @param \Drupal\foia_personnel\Entity\FoiaPersonnelInterface $foia_personnel
   *   A FOIA Personnel  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   */
  public function revisionOverview(FoiaPersonnelInterface $foia_personnel) {
    $account = $this->currentUser();
    $langcode = $foia_personnel->language()->getId();
    $langname = $foia_personnel->language()->getName();
    $languages = $foia_personnel->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $foia_personnel_storage = \Drupal::service('entity_type.manager')->getStorage('foia_personnel');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $foia_personnel->label()]) : $this->t('Revisions for %title', ['%title' => $foia_personnel->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all foia personnel revisions") || $account->hasPermission('administer foia personnel entities')));
    $delete_permission = (($account->hasPermission("delete all foia personnel revisions") || $account->hasPermission('administer foia personnel entities')));

    $rows = [];

    $vids = $foia_personnel_storage->revisionIds($foia_personnel);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\foia_personnel\FoiaPersonnelInterface $revision */
      $revision = $foia_personnel_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $foia_personnel->getRevisionId()) {
          // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
          // Please manually remove the `use LinkGeneratorTrait;` statement from this class.
          $link = Link::fromTextAndUrl($date, new Url('entity.foia_personnel.revision', ['foia_personnel' => $foia_personnel->id(), 'foia_personnel_revision' => $vid]));
        }
        else {
          // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
          // Please confirm that `$foia_personnel` is an instance of `\Drupal\Core\Entity\EntityInterface`. Only the method name and not the class name was checked for this replacement, so this may be a false positive.
          $link = $foia_personnel->toLink($date)->toString();
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
              'url' => Url::fromRoute('entity.foia_personnel.revision_revert', ['foia_personnel' => $foia_personnel->id(), 'foia_personnel_revision' => $vid]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.foia_personnel.revision_delete', ['foia_personnel' => $foia_personnel->id(), 'foia_personnel_revision' => $vid]),
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

    $build['foia_personnel_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
