<?php

namespace Drupal\foia_users\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\samlauth\Event\SamlauthEvents;
use Drupal\samlauth\Event\SamlauthUserAllowedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Controls whether SAML logins are permitted based on FOIA-specific rules.
 */
class FoiaUsersUserAllowedSubscriber implements EventSubscriberInterface {

  /**
   * The external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected ExternalAuthInterface $externalAuth;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Constructs the subscriber object.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external authentication service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(
    ExternalAuthInterface $external_auth,
    ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger
  ) {
    $this->externalAuth = $external_auth;
    $this->configFactory = $config_factory;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SamlauthEvents::USER_ALLOWED => ['onUserAllowed'],
    ];
  }

  /**
   * Responds to the samlauth USER_ALLOWED event.
   *
   * Logic:
   * 1. If the CSP attribute is missing in the SAML response → allow login (do nothing).
   * 2. If CSP *is* present:
   *      a. Determine the unique ID attribute configured in samlauth.
   *      b. Use ExternalAuth to load the corresponding Drupal account.
   *      c. If the user does not exist OR does not have the "non_sso" role → block login.
   *
   * @param \Drupal\samlauth\Event\SamlauthUserAllowedEvent $event
   *   The event object containing SAML attributes and allowing login decisions.
   */
  public function onUserAllowed(SamlauthUserAllowedEvent $event): void {
    $attributes = $event->getAttributes();
    if (empty($attributes['CSP'])) {
      return;
    }
    $config = $this->configFactory->get('samlauth.authentication');
    $unique_id_attr = $config->get('unique_id_attribute');
    if (empty($unique_id_attr) || empty($attributes[$unique_id_attr][0])) {
      $this->messenger->addError('Your account is not permitted to log in via SSO.');
      $event->disallow();
      return;
    }
    $unique_id_value = $attributes[$unique_id_attr][0];
    $account = $this->externalAuth->load($unique_id_value, 'samlauth');
    if (!$account || !$account->hasRole('non_sso')) {
      $this->messenger->addError('Your account is not permitted to log in via SSO.');
      $event->disallow();
    }
  }

}
