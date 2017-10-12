<?php

namespace Drupal\Tests\webform_serialization\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\webform\Entity\Webform;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

/**
 * Provides helper methods for the JSON API module's functional tests.
 */
abstract class WebformNormalizationFormOptionsTestBase extends BrowserTestBase {

  /**
   * The webform that we create.
   *
   * @var \Drupal\webform\Entity\Webform
   */
  protected $webform;

  public static $modules = [
    'jsonapi',
    'serialization',
    'webform',
    'webform_serialization',
    'webform_template',
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
   * Creates default content to test the API.
   */
  protected function createDefaultContent() {
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

    drupal_flush_all_caches();
  }

}
