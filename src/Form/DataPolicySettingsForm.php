<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DataPolicySettingsForm.
 *
 * @ingroup gdpr_consent
 */
class DataPolicySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdpr_consent_data_policy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gdpr_consent.data_policy'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gdpr_consent.data_policy');

    $form['enforce_consent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce consent'),
      '#description' => $this->t('A user should give your consent on data policy when he creates an account.'),
      '#default_value' => $config->get('enforce_consent'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('gdpr_consent.data_policy')
      ->set('enforce_consent', $form_state->getValue('enforce_consent'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
