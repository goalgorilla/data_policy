<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Data policy edit forms.
 *
 * @ingroup gdpr_consent
 */
class DataPolicyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['new_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => TRUE,
      '#disabled' => TRUE,
      '#weight' => 10,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    if ($form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();
      $entity->setRevisionCreationTime($this->time->getRequestTime());
      $entity->setRevisionUserId($this->currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $entity->save();

    $this->messenger()->addStatus($this->t('Saved data policy.'));

    $form_state->setRedirect('entity.data_policy.collection');
  }

}
