<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\gdpr_consent\Entity\DataPolicy;
use Drupal\gdpr_consent\GdprConsentManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DataPolicyAgreement.
 *
 * @ingroup gdpr_consent
 */
class DataPolicyAgreement extends FormBase {

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
   * DataPolicyAgreement constructor.
   *
   * @param \Drupal\gdpr_consent\GdprConsentManagerInterface $gdpr_consent_manager
   *   The GDPR consent manager.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $destination
   *   The redirect destination helper.
   */
  public function __construct(GdprConsentManagerInterface $gdpr_consent_manager, RedirectDestinationInterface $destination) {
    $this->gdprConsentManager = $gdpr_consent_manager;
    $this->destination = $destination;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gdpr_consent.manager'),
      $container->get('redirect.destination')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdpr_consent_data_policy_agreement';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->gdprConsentManager->saveConsent($this->currentUser()->id());

    $this->gdprConsentManager->addCheckbox($form);

    // Add a message that the data policy was updated.
    $entity_id = $this->config('gdpr_consent.data_policy')->get('entity_id');
    $timestamp = DataPolicy::load($entity_id)->getChangedTime();
    $date = \Drupal::service('date.formatter')->format($timestamp, 'html_date');
    $form['date'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'info' => [
          [
            '#type' => 'html_tag',
            '#tag' => 'strong',
            '#value' => t('Our data policy has been updated on %date', [
              '%date' => $date,
            ]),
          ],
        ],
      ],
      '#weight' => -1,
    ];

    if (!empty($this->config('gdpr_consent.data_policy')->get('enforce_consent'))) {
      $form['data_policy']['#weight'] = 1;

      $link = Link::createFromRoute($this->t('the account cancellation'), 'entity.user.cancel_form', [
        'user' => $this->currentUser()->id(),
      ]);

      $form['not_agree'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('Agreement to the data policy is required for continue using this platform. If you do not agree with the data policy, you will be guided to @url process.', [
          '@url' => $link->toString(),
        ]),
        '#theme_wrappers' => [
          'form_element',
        ],
        '#weight' => 0,
      ];
    }

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $agree = !empty($form_state->getValue('data_policy'));
    $enforce = $this->config('gdpr_consent.data_policy')->get('enforce_consent');

    $this->gdprConsentManager->saveConsent($this->currentUser()->id(), $agree);

    // If the user agrees or does not agree (but it is not enforced), check if
    // we should redirect him to the front page.
    if ($agree || (!$agree && empty($enforce))) {
      if ($this->destination->get() === '/data-policy-agreement') {
        $form_state->setRedirect('<front>');
      }
    }

    // If the user does not agree and it is enforced, we will redirect him to
    // the cancel account page.
    if (!$agree && !empty($enforce)) {
      $this->getRequest()->query->remove('destination');

      $form_state->setRedirect('entity.user.cancel_form', [
        'user' => $this->currentUser()->id(),
      ]);
    }
  }

}
