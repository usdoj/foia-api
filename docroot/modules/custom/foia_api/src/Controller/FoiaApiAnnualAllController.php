<?php

namespace Drupal\foia_api\Controller;

use Drupal\node\Entity\Node;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Controller\ControllerBase;
// Use Drupal\Core\Cache\JsonResponse;.
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\file\Entity\File;

/**
 * Gets jsonapi data.
 *
 * @package Drupal\foia_api\Controller
 */
class FoiaApiAnnualAllController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The number of seconds in a day, for use with the cache max age.
   */
  const CACHE_TIME = 60 * 60 * 24 * 365;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * FoiaApiFiscalYearController constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Cached response for report years array.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Standard Drupal Container Interface.
   *
   * @return \Drupal\Core\Controller\ControllerBase|void
   *   Returns DB instance.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * After report is run on frontend save to file.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Json Response should be cached
   */
  public function callback() {
    $result = [];

    // File repo
    // https://api.drupal.org/api/drupal/core%21modules%21file%21src%21FileRepository.php/function/FileRepository%3A%3AwriteData/9.3.x
    // Get blank file.
    $extension_list = \Drupal::service('extension.list.module');
    $filepath = $extension_list->getPath('foia_api') . '/assets/blank_file.txt';

    // @todo always empty
    $filedata = $_REQUEST['filedata'];
    $post = $_POST;

    $writeresult = file_put_contents($filepath, $filedata);

    // 4 means good
    switch ($writeresult) {
      case 4:
        $result['error'] = 0;
        break;

      default:
        $result['error'] = 1;
    }

    $directory = 'public://annual-reports';
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->prepareDirectory($directory, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $file_system->copy($filepath, $directory . '/' . basename($filepath), FileSystemInterface::EXISTS_REPLACE);

    // Create this file in public files directory.
    $file = File::create([
      'filename' => basename($filepath),
      'uri' => 'public://annual-reports/' . basename($filepath),
      'status' => 1,
      'uid' => 1,
    ]);
    $file->save();

    // Mark for usage??
    $file_usage = \Drupal::service('file.usage');
    $file_usage->add($file, 'foia_api', 'node', 1);

    $result['POST'] = $_POST;

    return new JsonResponse([
      'data' => $result,
      'method' => 'POST',
      'status' => 200,
    ]);

  }

  /**
   * Get data.
   */
  public function getData() {

    $result = [];
    $query = \Drupal::entityQuery('node')
      // ->condition('type', 'article')
      ->condition('type', 'annual_foia_report_data')
      ->sort('title', 'DESC');
    $nodes_ids = $query->execute();
    if ($nodes_ids) {
      foreach ($nodes_ids as $node_id) {
        $node = Node::load($node_id);
        $result[] = [
          "id" => $node->id(),
          "title" => $node->getTitle(),
        ];
      }
    }
    return $result;
  }

  /**
   * Get an array of report years for published Annual FOIA Report Data nodes.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON Response of Report Years cached for one day
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function get() {

    // https://dev-api.foia.gov/api/annual_foia_report?fields%5Bannual_foia_report_data%5D=title%2Cfield_foia_annual_report_yr%2Cfield_agency%2Cfield_agency_components%2Cfield_overall_req_pend_end_yr%2Cfield_overall_req_processed_yr%2Cfield_overall_req_received_yr%2Cfield_overall_req_pend_start_yr&fields%5Bfield_agency%5D=name%2Cabbreviation&fields%5Bfield_agency_components%5D=title&include=field_agency%2Cfield_agency_components%2Cfield_foia_requests_va%2Cfield_foia_requests_va.field_agency_component&page%5Boffset%5D=15&page%5Blimit%5D=5&filter%5Bstatus%5D=1&filter%5Bfiscal-year-2022%5D%5Bcondition%5D%5Bpath%5D=field_foia_annual_report_yr&filter%5Bfiscal-year-2022%5D%5Bcondition%5D%5Bvalue%5D=2022&filter%5Bfiscal-year-2022%5D%5Bcondition%5D%5BmemberOf%5D=or-filter-1&filter%5Bor-filter-1%5D%5Bgroup%5D%5Bconjunction%5D=OR
    $values = [
      'type' => 'page',
    ];

    // Get the nodes.
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('annual_foia_report_data')
      ->loadByProperties($values);
    return new JsonResponse([
      'data' => $nodes,
      'method' => 'POST',
      'status' => 200,
    ]);

  }

}
