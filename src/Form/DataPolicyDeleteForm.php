<?php

namespace Drupal\data_policy\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * asd
 *
 * @internal
 */
class DataPolicyDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    return parent::form($form, $form_state);
  }

}
