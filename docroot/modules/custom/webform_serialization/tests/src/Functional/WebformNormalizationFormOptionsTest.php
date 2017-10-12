<?php

namespace Drupal\Tests\webform_serialization\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\webform\Entity\WebformOptions;

/**
 * Tests for webform select options.
 *
 * @group jsonapi
 */
class WebformNormalizationFormOptionsTest extends WebformNormalizationFormOptionsTestBase {

  /**
   * Test the GET method.
   */
  public function testRead() {
    $element = $this->webform->getElement('state_province');
    /** @var \Drupal\webform\Entity\WebformOptions $webformOptions */
    $stateOptions = WebformOptions::getElementOptions($element);
    $this->createDefaultContent();
    // 5. Single article.
    $uuid = $this->webform->get('uuid');

    $single_output = Json::decode($this->drupalGet('/jsonapi/webform/webform/' . $uuid));

    $returnStateOptions = $single_output['data']['attributes']['elements']['state_province']['#options'];
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals($stateOptions, $returnStateOptions, "\$canonicalize = true", $delta = 0.0, $maxDepth = 10, $canonicalize = TRUE);
  }

}
