<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DataPolicyAgreement.
 *
 * @ingroup gdpr_consent
 */
class DataPolicyAgreement extends FormBase {

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
    \Drupal::service('gdpr_consent.manager')->addCheckbox($form);

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
    \Drupal::service('gdpr_consent.manager')->saveConsent(
      $this->currentUser()->id(),
      !empty($form_state->getValue('data_policy'))
    );

    if (\Drupal::destination()->get() == '/data-policy-agreement') {
      $form_state->setRedirect('<front>');
    }
  }

}
