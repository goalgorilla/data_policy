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
    if (\Drupal::currentUser()->isAnonymous() || \Drupal::currentUser()->id() == 1) {
      return;
    }

    $route_names = [
      'gdpr_consent.data_policy',
      'user.logout',
    ];

    if (in_array(\Drupal::routeMatch()->getRouteName(), $route_names)) {
      return;
    }

    $config = \Drupal::config('gdpr_consent.data_policy');

    if (empty($config->get('enforce_consent'))) {
      return;
    }

    $entity_id = $config->get('entity_id');

    /** @var \Drupal\gdpr_consent\DataPolicyStorageInterface $data_policy_storage */
    $data_policy_storage = \Drupal::entityTypeManager()->getStorage('data_policy');

    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $data_policy */
    $data_policy = $data_policy_storage->load($entity_id);

    $vids = $data_policy_storage->revisionIds($data_policy);

    $vid = end($vids);

    $user_consents = \Drupal::entityTypeManager()->getStorage('user_consent')
      ->loadByProperties([
        'user_id' => \Drupal::currentUser()->id(),
        'data_policy_revision_id' => $vid,
      ]);

    if (!empty($user_consents)) {
      return;
    }

    $event->setResponse(new RedirectResponse(Url::fromRoute('gdpr_consent.data_policy')->toString()));
  }

}
