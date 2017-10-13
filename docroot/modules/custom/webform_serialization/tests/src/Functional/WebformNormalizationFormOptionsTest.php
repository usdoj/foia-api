<?php

namespace Drupal\Tests\webform_serialization\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\webform\Entity\Webform;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Drupal\Component\Serialization\Json;
use Drupal\webform\Entity\WebformOptions;

/**
 * Tests for webform select options.
 *
 * @group jsonapi
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
    'rest',
    'user',
  ];

  /**
   * The HTTP Client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Set up a HTTP client that accepts relative URLs.
    $this->httpClient = $this->container->get('http_client_factory')
      ->fromOptions(['base_uri' => $this->baseUrl]);
  }

  /**
   * Performs a HTTP request. Wraps the Guzzle HTTP client.
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   */
  protected function request($method, Url $url, array $request_options) {
    try {
      $response = $this->httpClient->request($method, $url->toString(), $request_options);
    }
    catch (ClientException $e) {
      $response = $e->getResponse();
    }
    catch (ServerException $e) {
      $response = $e->getResponse();
    }

    return $response;
  }

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
