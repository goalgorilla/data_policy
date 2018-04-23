<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DataPolicySettingsForm.
 *
 * @ingroup gdpr_consent
 */
class DataPolicySettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdpr_consent_data_policy_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['datapolicy_settings']['#markup'] = 'Settings form for Data policy entities. Manage field settings here.';
    return $form;
  }

}
