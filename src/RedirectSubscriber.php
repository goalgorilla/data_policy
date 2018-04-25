<?php

namespace Drupal\gdpr_consent;

use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
   * The current active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The GDPR consent manager.
   *
   * @var \Drupal\gdpr_consent\GdprConsentManagerInterface
   */
  protected $gdprConsentManager;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $destination;

  /**
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current active route match object.
   * @param \Drupal\gdpr_consent\GdprConsentManagerInterface $gdpr_consent_manager
   *   The GDPR consent manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $destination
   *   The redirect destination helper.
   */
  public function __construct(RouteMatchInterface $route_match, GdprConsentManagerInterface $gdpr_consent_manager, RedirectDestinationInterface $destination) {
    $this->routeMatch = $route_match;
    $this->gdprConsentManager = $gdpr_consent_manager;
    $this->destination = $destination;
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

    if (in_array($this->routeMatch->getRouteName(), $route_names)) {
      return;
    }

    $url = Url::fromRoute('gdpr_consent.data_policy.agreement', [], [
      'query' => $this->destination->getAsArray(),
    ]);

    $response = new RedirectResponse($url->toString());
    $event->setResponse($response);
  }

}
