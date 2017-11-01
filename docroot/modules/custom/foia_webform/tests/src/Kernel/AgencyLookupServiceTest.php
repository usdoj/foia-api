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
      ->will($this->returnValue('a_test_webform'));

    $webformId = $webform->id();

    $etm = \Drupal::entityTypeManager();

    $lookup = new AgencyLookupService($etm);

    $return = $lookup->getComponentFromWebform($webformId);
print_r($return);
    $title = $return->label();

    $query = \Drupal::entityQuery('node')
      ->condition('field_request_submission_form', $webformId);
    $nids = $query->execute();

    $node = Node::load($nids[1]);

    $name = $node->label();

    // Title is the same as the one returned from getComponentByWebform.
    $this->assertEquals($name, $title);

    /* Tests getAgencyFromComponent. */
    // Adds Agency Field.
    $yml = yaml_parse(file_get_contents($path . '/field.storage.node.field_agency.yml'));
    FieldStorageConfig::create($yml)->save();
    $yml = yaml_parse(file_get_contents($path . '/field.field.node.agency_component.field_agency.yml'));
    FieldConfig::create($yml)->save();

    // Adds Agency Taxonomy Vocabulary.
    $yml = yaml_parse(file_get_contents($path . '/taxonomy.vocabulary.agency.yml'));
    Vocabulary::create($yml)->save();

    // Adds Agency Taxonomy Term.
    Term::create([
      'name' => 'A Test Taxonomy Term',
      'vid' => 'agency',
    ])->save();

    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', 'A Test Taxonomy Term');
    $tids = $query->execute();

    $term = Term::load($tids[1]);
    $name = $term->label();
    $etm = \Drupal::entityTypeManager();

    $lookup = new AgencyLookupService($etm);

    Node::create([
      'type' => 'agency_component',
      'title' => t('A Test Agency Component Associated with The Agency Agency'),
      'field_agency' => ['target_id' => 1],
      'field_portal_submission_format' => 'api',
      'field_submission_api' => 'http://atest.com',
      'field_request_submission_form' => ['target_id' => $webformId],
    ])->save();

    $query = \Drupal::entityQuery('node')
      ->condition('field_agency', 1);
    $nids = $query->execute();
    $node = Node::load($nids[2]);
    $return = $lookup->getAgencyFromComponent($node);
    $taxonomy_name = $return->label();
    $this->assertEquals($name, $taxonomy_name);

  }

}
