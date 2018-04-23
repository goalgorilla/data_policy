<?php

namespace Drupal\gdpr_consent;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RedirectSubscriber.
 *
 * @package Drupal\gdpr_consent
 */
class RedirectSubscriber implements EventSubscriberInterface {

  /**
   * The GDPR consent manager.
   *
   * @var \Drupal\gdpr_consent\GdprConsentManagerInterface
   */
  protected $gdprConsentManager;

  /**
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\gdpr_consent\GdprConsentManagerInterface $gdpr_consent_manager
   *   The GDPR consent manager.
   */
  public function __construct(GdprConsentManagerInterface $gdpr_consent_manager) {
    $this->gdprConsentManager = $gdpr_consent_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkForRedirection'];
    return $events;
  }

  /**
   * This method is called when the KernelEvents::REQUEST event is dispatched.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event.
   */
  public function checkForRedirection(GetResponseEvent $event) {
    if (!$this->gdprConsentManager->needConsent()) {
      return;
    }

    $route_names = [
      'gdpr_consent.data_policy',
      'gdpr_consent.data_policy.agreement',
      'user.logout',
    ];

    if (in_array(\Drupal::routeMatch()->getRouteName(), $route_names)) {
      return;
    }

    $url = Url::fromRoute('gdpr_consent.data_policy.agreement');
    $response = new RedirectResponse($url->toString());
    $event->setResponse($response);
  }

}
