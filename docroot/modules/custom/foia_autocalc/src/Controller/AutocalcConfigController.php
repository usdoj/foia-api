<?php

namespace Drupal\foia_autocalc\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\foia_autocalc\AutocalcConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a controller for autocalc configuration endpoints.
 */
class AutocalcConfigController extends ControllerBase {

  /**
   * The autocalc configuration service.
   *
   * @var \Drupal\foia_autocalc\AutocalcConfigInterface
   */
  protected $autocalcConfig;

  /**
   * Constructs a new AutocalcConfigController object.
   *
   * @param \Drupal\foia_autocalc\AutocalcConfigInterface $autocalc_config
   *   The autocalc configuration service.
   */
  public function __construct(AutocalcConfigInterface $autocalc_config) {
    $this->autocalcConfig = $autocalc_config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('autocalc.config')
    );
  }

  /**
   * Removes an autocalc configuration row.
   *
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field configuration object.
   * @param string $uuid
   *   The UUID of the row to remove.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Redirects the user to the current page.
   */
  public function removeConfigRow(FieldConfigInterface $field_config, $uuid) {
    $autocalc_settings = $field_config->getThirdPartySettings('foia_autocalc');
    unset($autocalc_settings['autocalc_settings']['autocalc_config'][$uuid]);
    $field_config->setThirdPartySetting('foia_autocalc', 'autocalc_settings', $autocalc_settings['autocalc_settings']);
    $field_config->save();

    $response = new AjaxResponse();
    $currentURL = Url::fromRoute('<current>');
    $response->addCommand(new RedirectCommand($currentURL->toString()));
    return $response;
  }

  /**
   * Provides an autocomplete matcher for autocalc config fields.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field configuration object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON object containing autocomplete matches.
   */
  public function fieldAutocomplete(Request $request, FieldConfigInterface $field_config) {
    $options = [];
    foreach ($this->autocalcConfig->getNumberFieldOptions($field_config) as $option) {
      $options[] = $option;
    }

    if ($query = $request->query->get('q')) {
      $query = mb_strtolower($query);
      $matches = array_filter($options, function ($var) use ($query) {
        return (strpos($var, $query) !== FALSE);
      });
    }
    else {
      $matches = $options;
    }

    return new JsonResponse(array_values($matches));
  }

}
