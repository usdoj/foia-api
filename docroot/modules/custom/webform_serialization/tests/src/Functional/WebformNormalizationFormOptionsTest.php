<?php

namespace Drupal\Tests\webform_serialization\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\Component\Serialization\Json;
use Drupal\webform\Entity\WebformOptions;

/**
 * Tests for webform select options.
 *
 * @group foiaapi
 */
class WebformNormalizationFormOptionsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'jsonapi',
    'serialization',
    'webform',
    'webform_serialization',
    'webform_template',
    'user',
  ];

  /**
   * Test the GET method.
   */
  public function testRead() {

    // Create webform.
    $this->webform = Webform::create(['id' => 'serialization_test']);
    $this->webform->set('foia_template', [
      '#type' => 'checkbox',
      '#title' => t('Use FOIA Agency template'),
      '#disabled' => TRUE,
      '#default_value' => 'foia_template',
      '#value' => 'foia_template',
    ]);
    $this->webform->save();
    $webform = Webform::load('serialization_test');
    $element = $webform->getElement('state_province');
    /** @var \Drupal\webform\Entity\WebformOptions $webformOptions */
    $stateOptions = WebformOptions::getElementOptions($element);
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, ['access content']);
    $uuid = $webform->get('uuid');
    $single_output = Json::decode($this->drupalGet('/jsonapi/webform/webform/' . $uuid));
    $this->assertSession()->statusCodeEquals(200);
    $returnStateOptions = $single_output['data']['attributes']['elements']['state_province']['#options'];
    $this->assertEquals($stateOptions, $returnStateOptions, "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = TRUE);

  }

}
