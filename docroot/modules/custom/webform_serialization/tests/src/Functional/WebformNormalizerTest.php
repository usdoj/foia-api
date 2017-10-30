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
class WebformNormalizerTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'jsonapi',
    'jsonapi_extras',
    'serialization',
    'webform',
    'webform_serialization',
    'webform_template',
    'user',
  ];

  /**
   * Test that jsonapi returns fully rendered webform options.
   */
  public function testPopulateSelectFieldsWithOptions() {
    // Create webform.
    $webform = Webform::create(['id' => 'serialization_test']);
    $webform->set('foia_template', 1);
    $webform->save();
    $webform = Webform::load('serialization_test');

    $uuid = $webform->uuid();
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, ['access content']);
    $webformFromJsonApi = Json::decode($this->drupalGet("/jsonapi/webform/webform/{$uuid}"));
    $this->assertSession()->statusCodeEquals(200);

    $webformElements = $webform->getElementsDecoded();
    foreach ($webformElements as $elementName => $webformElement) {
      if ($webformElement['#type'] === 'select' && !is_array($webformElement['#options'])) {
        $expectedElementOptions = WebformOptions::getElementOptions($webformElement);
        $elementOptionsFromJsonApi = $webformFromJsonApi['data']['attributes']['elements'][$elementName]['#options'];
        $this->assertEquals($expectedElementOptions, $elementOptionsFromJsonApi, "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = TRUE);
      }
    }

  }

}
