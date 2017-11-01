<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\foia_webform\AgencyLookupService;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Class AgencyLookupServiceTest.
 *
 * @package Drupal\Tests\foia_webform\Kernel
 */
class AgencyLookupServiceTest extends FoiaWebformKernelTestBase {

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
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');

  }

  /**
   * Tests Agency Lookup Service.
   */
  public function testAgencyLookupService() {

    // Tests getComponentFromWebform.
    $webform = $this->getMockBuilder('Drupal\webform\Entity\Webform')
      ->disableOriginalConstructor()
      ->setMethods(['id'])
      ->getMock();
    $webform->expects($this->once())
      ->method('id')
      ->will($this->returnValue('webform_with_template'));

    $webformId = $webform->id();
    $return = $this->agencyLookupService->getComponentFromWebform($webformId);
    $title = $return->label();

    $query = \Drupal::entityQuery('node')
      ->condition('field_request_submission_form', $webformId);
    $nids = $query->execute();

    $node = Node::load($nids[1]);

    $name = $node->label();

    // Title is the same as the one returned from getComponentByWebform.
    $this->assertEquals($name, $title);

    /* Tests getAgencyFromComponent. */
    $query = \Drupal::entityQuery('node')
      ->condition('field_agency', 1);
    $nids = $query->execute();
    $node = Node::load($nids[1]);
    $return = $this->agencyLookupService->getAgencyFromComponent($node);
    $taxonomy_name = $return->label();
    $this->assertEquals($name, $taxonomy_name);

  }

}
