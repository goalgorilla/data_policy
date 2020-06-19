<?php

namespace Drupal\data_policy\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * asd
 *
 * @internal
 */
class DataPolicyAddForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('Succesfully created a new "%name" entity.', ['%name' => $this->entity->label()]));
    $form_state->setRedirect('entity.data_policy.collection');

    return $this->entity;
  }

}
