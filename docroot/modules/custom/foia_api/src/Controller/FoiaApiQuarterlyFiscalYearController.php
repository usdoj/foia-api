<?php

namespace Drupal\foia_api\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets jsonapi data.
 *
 * @package Drupal\foia_api\Controller
 */
class FoiaApiQuarterlyFiscalYearController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The number of seconds in a day, for use with the cache max age.
   */
  const SECONDS_IN_A_DAY = 60 * 60 * 24;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * FoiaApiQuarterlyFiscalYearController constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container.
   *
   * @return \Drupal\Core\Controller\ControllerBase|void
   *   The controller.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Get an array of report years for published Quarterly Report nodes.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function get() {
    $query = $this->connection->select('node__field_quarterly_year', 'y')
      ->fields('y', ['field_quarterly_year_value']);
    $query->join('node_field_data', 'n', 'n.nid = y.entity_id');
    $query->condition('n.status', 1);
    $query->orderBy('y.field_quarterly_year_value', 'DESC');
    $data = $query->distinct()->execute()->fetchCol();
    $cache_tags[] = 'node_field:quarterly_report_yr';
    $cacheMeta = (new CacheableMetadata())
      ->setCacheTags($cache_tags)
      ->setCacheMaxAge(self::SECONDS_IN_A_DAY);
    $json_response = new CacheableJsonResponse($data);
    $json_response->addCacheableDependency($cacheMeta);
    return $json_response;
  }

}
