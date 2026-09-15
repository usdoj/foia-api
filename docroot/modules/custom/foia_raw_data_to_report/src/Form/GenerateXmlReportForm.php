<?php

namespace Drupal\foia_raw_data_to_report\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides the queued XML report generation action.
 */
final class GenerateXmlReportForm extends FormBase {

  /**
   * Constructs the report action form.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, protected QueueFactory $queueFactory) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('queue'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'foia_raw_data_to_report_generate_xml_report';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NodeInterface $node = NULL) {
    if (!$node || $node->bundle() !== 'raw_data_to_report') {
      return $form;
    }

    $form_state->set('node', $node);
    $access = $this->generationAccess($node);
    CacheableMetadata::createFromObject($access)->applyTo($form);
    $form['#access'] = $access->isAllowed();
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate XML Report'),
    ];
    return $form;
  }

  /**
   * Checks editing access and the agency manager's agency association.
   */
  protected function generationAccess(NodeInterface $node): AccessResultInterface {
    $account = $this->currentUser();
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $agency_matches = $user && $user->hasField('field_agency')
      && !$user->get('field_agency')->isEmpty()
      && $node->hasField('field_agency')
      && $user->get('field_agency')->target_id === $node->get('field_agency')->target_id;
    $allowed = $node->bundle() === 'raw_data_to_report'
      && ($account->hasPermission('bypass node access')
        || (in_array('agency_manager', $account->getRoles(), TRUE) && $agency_matches));
    $access = AccessResult::allowedIf($allowed)->cachePerUser()->addCacheableDependency($node);
    if ($user) {
      $access->addCacheableDependency($user);
    }
    return $access->andIf($node->access('view', $account, TRUE))
      ->andIf($node->access('update', $account, TRUE));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $node = $form_state->get('node');
    if (!$node instanceof NodeInterface || !$this->generationAccess($node)->isAllowed()) {
      throw new AccessDeniedHttpException();
    }
    if ($this->generateXmlReport($node)) {
      $this->messenger()->addStatus($this->t('XML report generation has been queued.'));
    }
    else {
      $this->messenger()->addError($this->t('The report could not be queued. Please try again.'));
    }
  }

  /**
   * Enqueues the node ID without reading or processing the CSV file yet.
   */
  protected function generateXmlReport(NodeInterface $node): bool {
    return $this->queueFactory->get('raw_data_to_report_processing')->createItem([
      'nid' => (int) $node->id(),
    ]) !== FALSE;
  }

}
