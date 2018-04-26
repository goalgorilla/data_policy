<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
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
   * DataPolicyAgreement constructor.
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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('gdpr_consent.manager')
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
    $this->gdprConsentManager->addCheckbox($form);

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
    $this->gdprConsentManager->saveConsent(
      $this->currentUser()->id(),
      !empty($form_state->getValue('data_policy'))
    );

    if (\Drupal::destination()->get() == '/data-policy-agreement') {
      $form_state->setRedirect('<front>');
    }
  }

}
