<?php

namespace Drupal\foia_raw_data_to_report\EventSubscriber;

use Drupal\foia_raw_data_to_report\ReportNotifications;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Delivers stored report outcomes during normal browser page visits.
 */
final class ReportNotificationSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the subscriber.
   */
  public function __construct(protected ReportNotifications $notifications) {
  }

  /**
   * Adds notices before dynamic page cache can serve an HTML response.
   */
  public function onRequest(RequestEvent $event): void {
    $request = $event->getRequest();
    if ($event->isMainRequest() && $request->isMethod('GET') && !$request->isXmlHttpRequest() && $request->getRequestFormat() === 'html' && !$request->query->has('_wrapper_format')) {
      $this->notifications->deliver();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // After routing (32), before dynamic page cache (27).
    return [KernelEvents::REQUEST => ['onRequest', 30]];
  }

}
