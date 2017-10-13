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

    $uuid = $webform->get('uuid');
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, ['access content']);
    $webformFromJsonApi = Json::decode($this->drupalGet('/jsonapi/webform/webform/' . $uuid));
    $this->assertSession()->statusCodeEquals(200);

    $webformElements = $webform->getElementsInitialized();
    foreach ($webformElements as $elementName => $webformElement) {
      if ($webformElement['#type'] === 'select' && !is_array($webformElement['#options'])) {
        /** @var \Drupal\webform\Entity\WebformOptions $webformOptions */
        $expectedElementOptions = WebformOptions::getElementOptions($elementName);
        $elementOptionsFromJsonApi = $webformFromJsonApi['data']['attributes']['elements'][$elementName]['#options'];
        $this->assertEquals($expectedElementOptions, $elementOptionsFromJsonApi, "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = TRUE);
      }
    }

  }

}
