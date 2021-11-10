<?php

namespace Drupal\foia_cfo\Controller;

/**
 * @file
 * Contains \Drupal\foia_cfo\Controller\CFOPagesController.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;

/**
 * Controller routines for foia_cfo routes.
 */
class CFOPagesController extends ControllerBase {

  /**
   * Node Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $nodeStorage;

  /**
   * Constructor for this class.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct() {
    $this->nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
  }

  /**
   * Callback for `api/cfo/pages` API method returns JSON Response.
   *
   * Returns array of node ids, page title, url slug and updated.
   * The slug can then be passed to the page detail callback
   * for full details of the page.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON for the pages list.
   */
  public function getPages(): CacheableJsonResponse {

    // Initialize the response.
    $response = [];

    // Wrap Query in render context.
    $context = new RenderContext();
    $page_nids = \Drupal::service('renderer')->executeInRenderContext($context, function () {
      $page_query = \Drupal::entityQuery('node')
        ->condition('type', 'cfo_page')
        ->condition('status', 1)
        ->sort('created');
      return $page_query->execute();
    });

    if (!empty($page_nids)) {

      // Loop through all pages.
      foreach ($page_nids as $page_nid) {

        // Load the page node.
        if ($page_node = $this->nodeStorage->load($page_nid)) {
          $page = [
            'page_nid' => $page_nid,
            'page_title' => $page_node->label(),
            'page_updated' => $page_node->changed->value,
            'page_slug' => $page_node->get('field_cfo_slug')->getValue()[0]['value'],
          ];
          $response[] = $page;
        }

      }

    }

    // Set up the Cache Meta.
    $cacheMeta = (new CacheableMetadata())
      ->setCacheTags(['node_list:cfo_page'])
      ->setCacheMaxAge(Cache::PERMANENT);

    // Set the JSON response to the response of pages.
    $json_response = new CacheableJsonResponse($response);

    // Add in the cache dependencies.
    $json_response->addCacheableDependency($cacheMeta);

    // Handle any bubbled cacheability metadata.
    if (!$context->isEmpty()) {
      $bubbleable_metadata = $context->pop();
      BubbleableMetadata::createFromObject($page_nids)
        ->merge($bubbleable_metadata);
    }

    // Return JSON Response.
    return $json_response;

  }

  /**
   * Callback for `api/cfo/page/{slug}` API method.
   *
   * Returns JSON Response full details for a page based on node id passed.
   *
   * @param string $slug
   *   Node object of the page passed as argument through routing.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Returns json object or false if the node did not load.
   */
  public function getPage(string $slug): CacheableJsonResponse {

    // Use this function to get the meeting node from the date string.
    $page = \Drupal::service('foia_cfo.default')->contentFromSlug($slug, 'cfo_page');

    if (
      !empty($page)
      && $page->isPublished()
      && $page->bundle() === 'cfo_page'
    ) {

      // Initialize the response with basic info.
      $response = [
        'page_title' => $page->label(),
        'page_updated' => $page->changed->value,
      ];

      // Add body HTML if any - use absolute links.
      if (
        $page->hasField('body')
        && !empty($page->get('body'))
        && !empty($page->get('body')->getValue()[0]['value'])
      ) {
        $response['page_body'] = \Drupal::service('foia_cfo.default')->absolutePathFormatter($page->get('body')->getValue()[0]['value']);
      }

      // Set up the Cache Meta.
      $cacheMeta = (new CacheableMetadata())
        ->setCacheTags(['node:' . $page->id()])
        ->setCacheMaxAge(Cache::PERMANENT);

      // Set the JSON response to the response of page data.
      $json_response = new CacheableJsonResponse($response);

      // Add in the cache dependencies.
      $json_response->addCacheableDependency($cacheMeta);

      // Return JSON Response.
      return $json_response;

    }

    else {

      // Not a valid page or not published.
      return new CacheableJsonResponse([]);

    }

  }

}
