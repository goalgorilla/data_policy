<?php

namespace Drupal\data_policy\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DataPolicyAddForm.
 *
 * @package Drupal\data_policy\Form
 */
class DataPolicyAddForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('Succesfully created a new "%name" entity.', ['%name' => $this->entity->label()]));
    $form_state->setRedirect('entity.data_policy.collection');

    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $route_name = \Drupal::routeMatch()->getRouteName();

    if ($route_name === 'entity.data_policy.revision_add') {
      $entity_id = \Drupal::request()->get('entity_id');
      $this->entity = $this->entityTypeManager->getStorage('data_policy')->load($entity_id);

      $this->entity->setNewRevision(TRUE);

      $active_revision = !empty($form_state->getValue('active_revision'));

      if (!$active_revision) {
        $this->entity->isDefaultRevision(FALSE);
      }

      $this->entity->set('name', $form_state->getValue('name'));
      $this->entity->set('field_description', $form_state->getValue('field_description'));
      $this->entity->set('revision_log_message', $form_state->getValue('revision_log_message'));
      $this->entity->setRevisionCreationTime($this->time->getRequestTime());
      $this->entity->setRevisionUserId($this->currentUser()->id());

      $this->entity->save();
      $this->messenger()->addStatus($this->t('Created new revision.'));

      $form_state->setRedirect('entity.data_policy.version_history', ['entity_id' => $entity_id]);
    }
    else {
      return parent::save($form, $form_state);
    }

  }

}
