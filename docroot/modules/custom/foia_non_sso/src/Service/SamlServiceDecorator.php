<?php

namespace Drupal\foia_non_sso\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\externalauth\Authmap;
use Drupal\externalauth\ExternalAuth;
use Drupal\samlauth\SamlService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Decorates samlauth's SamlService to enforce FOIA Non-SSO login rules.
 *
 * This decorator blocks SAML logins if:
 *   - the SAML assertion contains attribute "CSP"
 *   - the Drupal account does NOT have the "non_sso" role.
 *
 * This applies only to SAML logins. Other login methods are unaffected.
 */
class SamlServiceDecorator extends SamlService {

  /**
   * Logger for rule enforcement events.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $foiaLogger;

  /**
   * Constructs the decorator.
   *
   * @param \Drupal\externalauth\ExternalAuth $external_auth
   *   ExternalAuth service.
   * @param \Drupal\externalauth\Authmap $authmap
   *   Authmap service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Logger channel for FOIA events.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Request stack.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   TempStore factory.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   Flood service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   Translation service.
   */
  public function __construct(
    ExternalAuth $external_auth,
    Authmap $authmap,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelInterface $logger,
    EventDispatcherInterface $event_dispatcher,
    RequestStack $request_stack,
    PrivateTempStoreFactory $temp_store_factory,
    FloodInterface $flood,
    AccountInterface $current_user,
    MessengerInterface $messenger,
    TranslationInterface $translation
  ) {
    parent::__construct(
      $external_auth,
      $authmap,
      $config_factory,
      $entity_type_manager,
      $logger,
      $event_dispatcher,
      $request_stack,
      $temp_store_factory,
      $flood,
      $current_user,
      $messenger,
      $translation
    );

    $this->foiaLogger = $logger;
  }

  /**
   * {@inheritdoc}
   *
   * Implements FOIA Non-SSO rule:
   *   - If SAML contains "CSP" and the user lacks "non_sso", deny login.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   When login is rejected.
   */
  protected function doLogin($unique_id, ?AccountInterface $account = NULL) {
    $attributes = $this->getAttributes();
    $csp_present = isset($attributes['CSP']) && !empty($attributes['CSP']);
    $login_gov_csp_values = [
      'https://idp.int.identitysandbox.gov/api/saml',
      'https://secure.login.gov/api/saml',
    ];
    $login_gov = $csp_present && in_array($attributes['CSP'], $login_gov_csp_values);

    if ($login_gov && $account && !$account->hasRole('non_sso')) {
      $this->foiaLogger->warning(
        'FOIA rule: login blocked for uid @uid because CSP attribute is present and user lacks non_sso role.',
        ['@uid' => $account->id()]
      );

      throw new AccessDeniedHttpException('Login rejected due to FOIA Non-SSO policy.');
    }

    return parent::doLogin($unique_id, $account);
  }

}
