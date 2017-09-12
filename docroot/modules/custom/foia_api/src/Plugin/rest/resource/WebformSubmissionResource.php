<?php

namespace Drupal\foia_api\Plugin\rest\resource;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents webform submissions as a resource.
 *
 * @RestResource(
 *   id = "webform_submission",
 *   label = @Translation("Webform submission"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/api/webform/submit",
 *   },
 * )
 */
class WebformSubmissionResource extends ResourceBase {

  const INVALID_FORM_ID_ERROR = 'Invalid form ID. Check the agency metadata for the latest form information for the desired agency component.';

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Constructs a new WebformSubmissionResource instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializerFormats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   Entity query service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializerFormats, LoggerInterface $logger, QueryFactory $queryFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializerFormats, $logger);
    $this->queryFactory = $queryFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function post(array $data) {
    $webformId = isset($data['id']) ? $data['id'] : '';

    if (!$webformId) {
      return new ModifiedResourceResponse(['errors' => "Missing form 'id'."], 400);
    }

    $values = [
      'webform_id' => $webformId,
    ];
    unset($data['id']);
    $values['data'] = $data;

    // Check if webform exists.
    $webform = Webform::load($webformId);
    if (!$webform) {
      return new ModifiedResourceResponse(['errors' => WebformSubmissionResource::INVALID_FORM_ID_ERROR], 422);
    }

    if (!$this->isAssociatedToAgencyComponent($webformId)) {
      return new ModifiedResourceResponse(['errors' => WebformSubmissionResource::INVALID_FORM_ID_ERROR], 403);
    }

    $isWebformAcceptingSubmissions = WebformSubmissionForm::isOpen($webform);
    if (!$isWebformAcceptingSubmissions) {
      return new ModifiedResourceResponse(['errors' => WebformSubmissionResource::INVALID_FORM_ID_ERROR], 422);
    }

    // Validate submission.
    $errors = WebformSubmissionForm::validateValues($values);
    if (!empty($errors)) {
      return new ModifiedResourceResponse(['errors' => $errors]);
    }

    // Perform submission.
    $webformSubmission = WebformSubmissionForm::submitValues($values);
    return new ModifiedResourceResponse(['submission_id' => $webformSubmission->id()], 201);
  }

  /**
   * Checks if the webform being submitted is associated to an agency component.
   *
   * @param string $webformId
   *   The ID of the webform to perform an association lookup against.
   *
   * @return bool
   *   TRUE if the webform is associated to an agency component, otherwise
   *   FALSE.
   */
  protected function isAssociatedToAgencyComponent($webformId) {
    $query = $this->queryFactory->get('node');
    $query->condition('field_request_submission_form', $webformId);
    $nids = $query->execute();
    if ($nids) {
      return TRUE;
    }
    return FALSE;
  }

}
