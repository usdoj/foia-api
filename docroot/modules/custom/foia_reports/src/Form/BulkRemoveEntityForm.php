<?php

namespace Drupal\foia_reports\Form;

use Drupal;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Class BulkRemoveEntityForm.
 *
 * Provide a form to upload agency annual reports in NIEM-XML format.
 */
class BulkRemoveEntityForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * The enitty id.
   *
   * @var id
   */
  protected $id;
  /**
   * The form step number.
   * @var step
   */
  protected $step = 1;

  /**
   * BulkRemoveEntityForm constructor.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(Drupal\Core\Entity\EntityTypeManagerInterface ity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'foia_remove_revision_form';
  }

  /**
   * {@inheritdoc}
   * @params : (int) $node, node ID.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    $limit = 20;
    $nid = $node;
    $this->id = $nid;
    $header = [
      'vid' => ['data' => $this->t('vid')],
      'nid' => ['data' => $this->t('nid')],
      'column' => ['data' => $this->t('Revision')],
      'status' => ['data' => $this->t('Status')],
    ];
    $node_obj = Node::load($nid);
    $langcode = $node_obj->language()->getId();
    $langname = $node_obj->language()->getName();
    $languages = $node_obj->getTranslationLanguages();
    try {
      $node_storage = Drupal::entityTypeManager()->getStorage('node');
    }
    catch (InvalidPluginDefinitionException $e) {
    }
    catch (PluginNotFoundException $e) {
    }
    if ($this->step === 2) {
      $values = $form_state->getValue('revisions');
      $selected_vids = [];
      foreach ($values as $vid) {
        if ($vid) {
          $selected_vids[] = $vid;
        }
      }
      $vids = $selected_vids;
      unset($header['status']);
    } else {
      /** @var object $node_obj */
      $result = $node_storage->getQuery()
        ->allRevisions()
        ->condition('nid', $nid)
        ->sort($node_obj->getEntityType()->getKey('revision'), 'DESC')
        ->pager($limit)
        ->execute();
      $vids = array_keys($result);
    }
    $rows = [];
    $page = Drupal::request()->query->get('page');
    $default_revision = $node_obj->getRevisionId();
    $current_revision_displayed = FALSE;
    $start = $page * $limit + 1;
    if ($vids) {
      foreach ($vids as $vid) {
        /** @var NodeInterface $revision */
        $revision = $node_storage->loadRevision($vid);
        // Only show revisions that are affected by the language that is being
        // displayed.
        $row = [
          'vid' => $vid,
          'nid' => $revision->get('nid')->value,
        ];

        if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
          $username = [
            '#theme' => 'username',
            '#account' => $revision->getRevisionUser(),
          ];
          // Use revision link to link to revisions that are not active.
          $date = Drupal::service('date.formatter')->format($revision->revision_timestamp->value, 'short');
          // We treat also the latest translation-affecting revision as current
          // revision, if it was the default revision, as its values for the
          // current language will be the same of the current default revision in
          // this case.
          $is_current_revision = ($vid == $default_revision);
          if (!$is_current_revision) {
            $link = $this->l($date, new Url('entity.node.revision', ['node' => $node_obj->id(), 'node_revision' => $vid]));
          } else
            {
            try {
              $link = $node_obj->toLink($date)->toString();
            }
            catch (Drupal\Core\Entity\EntityMalformedException $e) {
            }
            $current_revision_displayed = TRUE;
          }

          $column = [
            'data' => [
              '#type' => 'inline_template',
              '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
              '#context' => [
                'date' => $link,
                'username' => Drupal::service('renderer')->renderPlain($username),
                'message' => ['#markup' => $revision->revision_log->value, '#allowed_tags' => Xss::getHtmlTagList()],
              ],
            ],
          ];

          Drupal::service('renderer')->addCacheableDependency($column['data'], $username);
          $row['column'] = $column;
        }
        if ($is_current_revision) {
          $row['status'] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision, this revision will not remove'),
              '#suffix' => '</em>',
            ],
          ];
          $row['#disabled'] = TRUE;
          $row['#attributes'] = ['disable' => TRUE];
          $option_value = 0;
        }
        else {
          $row['status']['data'] = '';
          $option_value = $vid;
        }
        if (2 === $this->step) {
          unset($row['status']);
        }
        $rows[$option_value] = $row;
        $start++;
      }
    }
    if (2 === $this->step) {
      $values = $form_state->getValue('revisions');
      $selected_vids = [];
      foreach ($values as $vid) {
        if ($vid) {
          $selected_vids[] = $vid;
        }
      }

      $form['confirm_revisions'] = [
        '#type' => 'hidden',
        '#default_value' => implode(',', $selected_vids),
      ];
      $form['selected_item'] = [
        '#type' => 'item',
        '#markup' => '<h2>' . $this->t("Are you sure you want to remove these selected revisions? This action can't be undone.") . '</h2>',
      ];
      $form['revisions'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Confirm Removed Revisions'),
      ];
      $form['actions']['cancel'] = [
        '#title' => $this->t('Cancel'),
        '#type' => 'link',
        '#url' => Url::fromRoute('foia_reports.revisions', ['node' => $nid]),
      ];
    }
    else {
      $form['revisions'] = [
        '#type' => 'tableselect',
        '#header' => $header,
        '#options' => $rows,
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove Selected Revisions'),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The confirmation step needs no additional validation.
    if (2 === $this->step) {
      return;
    }
    $values = $form_state->getValue('revisions');
    $selected_vids = [];
    foreach ($values as $vid) {
      if ($vid) {
        $selected_vids[] = $vid;
      }
    }
    if (empty($selected_vids)) {
      $form_state->setErrorByName('revisions', $this->t('Please select atleast one revision to be delete! Please make sure the revision version should not be a <em>Current revision</em>.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->step === 1) {
      $form_state->setRebuild();
      $this->step = 2;
      return;
    }
    else {
      $vids = $form_state->getValue('confirm_revisions');
      $vids_arr = explode(',', $vids);
      foreach ($vids_arr as $vid) {
        try {
          Drupal::entityTypeManager()->getStorage('node')->deleteRevision($vid);
        }
        catch (InvalidPluginDefinitionException $e) {
        }
        catch (PluginNotFoundException $e) {
        }
      }
      drupal_set_message(t('Selected revisions has been deleted'), 'status', TRUE);
      $url = Url::fromRoute('foia_reports.revisions', ['node' => $this->id]);
      $form_state->setRedirectUrl($url);
    }
  }

}
