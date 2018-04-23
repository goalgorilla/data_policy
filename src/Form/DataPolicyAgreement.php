<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\gdpr_consent\Entity\DataPolicy;
use Drupal\gdpr_consent\Entity\UserConsent;

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
    static::addCheckbox($form);

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
    static::saveConsent($this->currentUser()->id());

    if (\Drupal::destination()->get() == '/data-policy-agreement') {
      $form_state->setRedirect('<front>');
    }
  }

  /**
   * Add checkbox to form which allow user give consent on data policy.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param bool $required
   *   TRUE if the field should be required.
   */
  public static function addCheckbox(array &$form, $required = TRUE) {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $link = Link::createFromRoute(t('data policy'), 'gdpr_consent.data_policy', [], [
      'attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'title' => t('Data policy'),
          'width' => 700,
          'height' => 700,
        ]),
      ],
    ]);

    $form['data_policy'] = [
      '#type' => 'checkbox',
      '#title' => t('I agree with the @url', [
        '@url' => $link->toString(),
      ]),
      '#required' => $required,
    ];
  }

  /**
   * Save user consent.
   *
   * @param int $user_id
   *   The user ID.
   */
  public static function saveConsent($user_id) {
    $user_consent = UserConsent::create();

    $user_consent->setOwnerId($user_id);

    $entity_id = \Drupal::config('gdpr_consent.data_policy')->get('entity_id');

    $user_consent->setRevision(DataPolicy::load($entity_id));

    $user_consent->save();
  }

}
