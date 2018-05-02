<?php

namespace Drupal\gdpr_consent;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
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

  use StringTranslationTrait;

  /**
   * The current active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $destination;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current active route match object.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $destination
   *   The redirect destination helper.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(RouteMatchInterface $route_match, RedirectDestinationInterface $destination, AccountProxyInterface $current_user, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    $this->routeMatch = $route_match;
    $this->destination = $destination;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
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
    $route_name = $this->routeMatch->getRouteName();

    if ($route_name == 'gdpr_consent.data_policy.agreement') {
      return;
    }

    if ($this->currentUser->hasPermission('without consent')) {
      return;
    }

    $config = $this->configFactory->get('gdpr_consent.data_policy');

    $entity_id = $config->get('entity_id');

    /** @var \Drupal\gdpr_consent\DataPolicyStorageInterface $data_policy_storage */
    $data_policy_storage = $this->entityTypeManager->getStorage('data_policy');

    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $data_policy */
    $data_policy = $data_policy_storage->load($entity_id);

    $vids = $data_policy_storage->revisionIds($data_policy);

    $vid = end($vids);

    $values = [
      'user_id' => $this->currentUser->id(),
      'data_policy_revision_id' => $vid,
    ];

    if ($enforce_consent = !empty($config->get('enforce_consent'))) {
      $values['status'] = TRUE;
    }

    $user_consents = $this->entityTypeManager->getStorage('user_consent')
      ->loadByProperties($values);

    if (!empty($user_consents)) {
      return;
    }

    if (!$enforce_consent) {
      $link = Link::createFromRoute($this->t('here'), 'gdpr_consent.data_policy.agreement');

      $this->messenger->addStatus($this->t('We published a new version of the data policy. You can review the data policy @url.', [
        '@url' => $link->toString(),
      ]));

      return;
    }

    $route_names = [
      'entity.user.cancel_form',
      'entity.user.edit_form',
      'gdpr_consent.data_policy',
      'system.404',
      'system.batch_page.html',
      'system.batch_page.json',
      'user.cancel_confirm',
      'user.logout',
    ];

    if (in_array($route_name, $route_names)) {
      return;
    }

    $url = Url::fromRoute('gdpr_consent.data_policy.agreement', [], [
      'query' => $this->destination->getAsArray(),
    ]);

    $response = new RedirectResponse($url->toString());
    $event->setResponse($response);
  }

}
