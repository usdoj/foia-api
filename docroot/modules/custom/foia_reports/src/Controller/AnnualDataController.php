<?php

namespace Drupal\foia_reports\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\Core\Link;

/**
 * Class AnnualDataController.
 */
class AnnualDataController extends ControllerBase
{

  /**
   * From the content of a node.
   *
   * Docx file response
   */
  public function report() {
    $limit = 20;
    $header = [
      // We make it sortable by name.
      ['data' => $this->t('S. No.')],
      ['data' => $this->t('Title'), 'field1' => 'title'],
      ['data' => $this->t('Created On'), 'field1' => 'created'],
      ['data' => $this->t('Updated On'), 'field1' => 'changed', 'sort' => 'desc'],
      '',
      ''
    ];
    $query = Drupal::entityQuery('node')
      ->condition('type', 'annual_foia_report_data')
      ->pager($limit);
    $nids = $query->execute();
    // LoadMultiple node in $nodes variable.
    $nodes = Node::loadMultiple($nids);

    $rows = [];
    $page = Drupal::request()->query->get('page');

    $start = $page * $limit + 1;
    if ($nodes) {
      foreach ($nodes as $node) {
        $rows[] = [
          'sno' => $start,
          'title' => Link::createFromRoute($node->getTitle(), 'entity.node.canonical', ['node' => $node->id()])
            ->toString(),
          'created' => Drupal::service('date.formatter')
            ->format($node->getCreatedTime(), 'short'),
          'changed' => Drupal::service('date.formatter')
            ->format($node->getChangedTime(), 'short'),
          'remove_revisions' => Link::createFromRoute('Remove Revision', 'foia_reports.revisions', ['node' => $node->id()])
            ->toString(),
          'revisions' => Link::createFromRoute(
            'Revisions',
            'entity.node.version_history',
            [
              'node' => $node->id()
            ])
            ->toString()
        ];
        $start++;
      }
    }
    // Return $nodes variable with name items to the module.
    $build['result'] = [
      '#type' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];
    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

  /**
   * @return mixed
   */
  public function revisions()
  {
    $build['pager'] = [
      '#markup' => '<h2>Coming Soon...</h2>',
    ];

    return $build;
  }

}
