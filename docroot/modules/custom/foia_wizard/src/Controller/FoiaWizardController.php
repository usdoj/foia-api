<?php

namespace Drupal\foia_wizard\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * FoiaWizardController constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
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
      $container->get('config.factory'),
      $container->get('request_stack')
    );
  }

  /**
   * Builds the response.
   */
  public function wizard() {

    // Get the config.
    $config = $this->configFactory->get('foia_wizard.settings');

    $messages = [];

    // Add rich text fields for messages.
    for ($i = 1; $i <= FOIA_WIZARD_MCOUNT; $i++) {
      $messages['m' . $i] = $config->get('messages')['m' . $i]['value'];
    }

    $data = [
      'language' => [
        'en' => [
          'intro_slide' => $config->get('intro_slide.value'),
          'query_slide' => $config->get('query_slide.value'),
          'messages' => $messages,
        ],
      ],
    ];

    $valid_origins = [
      'https://www.foia.gov',
      'https://main-bvxea6i-oafzps2pqxjxw.us-2.platformsh.site',
    ];

    $origin = $this->requestStack->getCurrentRequest()->headers->get('Origin', '');

    $headers = in_array($origin, $valid_origins)
      ? ['Access-Control-Allow-Origin' => $origin]
      : [];

    // Return JSON response.
    return new JsonResponse($data, 200, $headers);
    //return CacheableJsonResponse::create($data)->setMaxAge(self::SECONDS_IN_A_DAY);
  }

}
