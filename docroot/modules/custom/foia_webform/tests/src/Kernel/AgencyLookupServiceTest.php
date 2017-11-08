<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\foia_webform\AgencyLookupService;
use Drupal\node\Entity\Node;

/**
 * Class AgencyLookupServiceTest.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
class AgencyLookupServiceTest extends FoiaWebformApiKernelTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference',
    'filter',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system', 'taxonomy']);
    $this->installSchema('user', 'users_data');
  }

  /**
   * Tests Agency Lookup Service.
   */
  public function testAgencyLookupService() {

    $webformId = $this->webform->id();
    $etm = \Drupal::entityTypeManager();
    $lookup = new AgencyLookupService($etm);
    $return = $lookup->getComponentFromWebform($webformId);
    $title = $return->label();

    $query = \Drupal::entityQuery('node')
      ->condition('field_request_submission_form', $webformId);
    $nids = $query->execute();
    $node = Node::load($nids[1]);
    $name = $node->label();

    // Title is the same as the one returned from getComponentByWebform.
    $this->assertEquals($name, $title);

    $termName = $this->agency->label();
    $return = $lookup->getAgencyFromComponent($node);
    $taxonomy_name = $return->label();
    $this->assertEquals($termName, $taxonomy_name);

  }

}
