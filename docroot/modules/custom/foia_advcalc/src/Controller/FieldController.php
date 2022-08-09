<?php

namespace Drupal\foia_advcalc\Controller;

/**
 * Field controller that may not be used.
 *
 * @todo may not need this, but will be used for any AJAX.
 *
 * @file
 * Contains \Drupal\foia_advcalc\Controller\FieldController.
 */

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RenderContext;

/**
 * Controller routines for foia_cfo routes.
 */
class FieldController extends ControllerBase {

  /**
   * Node Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $nodeStorage;

  /**
   * View Builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  private $viewBuilder;

  /**
   * Render Service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderService;

  /**
   * Constructor for this class.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct() {
    // @todo These should be passed in to the constructor in the usual style.
    $this->nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
    $this->viewBuilder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $this->renderService = \Drupal::service('renderer');
  }

  /**
   * FOR AJAX CALCULATIONS, NOT USING AS OF NOW.
   */
  public function calculatePercentTotalCostsController(int $nid, string $values): CacheableJsonResponse {

    // Values into array of values.
    $error = 1;
    $message = "You must first fill in other field before this one can be calculated.";
    $response = [
      'field' => 'testasdf',
      'error' => $error,
      'message' => $message,
      'nid' => $nid,
      'values' => $values,

    ];
    $json_response = new CacheableJsonResponse($response);
    return $json_response;

  }

  /**
   * Callback for `api/foia_advcalc/field` API method returns JSON Response.
   *
   * The "Percentage of Total Costs" field is calculated by comparing the
   *   "Fees Collected" field with "Processing Costs" from section IX.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Returns calculated amount "Percentage of Total Costs" field in JSON
   */
  public function getField(string $field, int $nid): CacheableJsonResponse {

    // Initialize the response.
    $response = [];

    // Array to hold cache tags for this feed.
    $cache_tags = [];

    $context = new RenderContext();
    $council_query = \Drupal::entityQuery('node')
      ->condition('type', 'cfo_council')
      ->condition('status', 1)
      ->sort('created')
      ->range(0, 1)
      ->execute();

    $response = ['test' => 'testasdf'];
    $json_response = new CacheableJsonResponse($response);
    return $json_response;

  }

}
