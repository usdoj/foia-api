<?php

namespace Drupal\foia_wizard\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for FOIA Request Wizard routes.
 */
class FoiaWizardController extends ControllerBase {

  /**
   * The number of seconds in a day, for use with the cache max age.
   */
  const SECONDS_IN_A_DAY = 60 * 60 * 24;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * FoiaWizardController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Cached response for report years array.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Standard Drupal Container Interface.
   *
   * @return \Drupal\Core\Controller\ControllerBase|void
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Builds the response.
   */
  public function wizard() {

    // Get the config.
    $config = $this->configFactory->get('foia_wizard.settings');

    // Get the title.
    $data = [
      'language' => [
        'es' => [
          'intro_slide' => $config->get('intro_slide.value'),
          'foo' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit',
          'bar' => 'Lorem ipsum dolor sit amet',
        ]
      ]
    ];

    // Return JSON response.
    return new JsonResponse($data);
    //return CacheableJsonResponse::create($data)->setMaxAge(self::SECONDS_IN_A_DAY);

  }

}
