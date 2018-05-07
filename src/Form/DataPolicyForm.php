<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Form controller for Data policy edit forms.
 *
 * @ingroup gdpr_consent
 */
class DataPolicyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  protected function prepareEntity() {
    parent::prepareEntity();

    if (!$this->getEntity()->isNew()) {
      return;
    }

    $entity_id = $this->config('gdpr_consent.data_policy')->get('entity_id');

    if (empty($entity_id)) {
      return;
    }

    $entity = $this->entityTypeManager->getStorage('data_policy')
      ->load($entity_id);

    $this->setEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($this->clearMessage()) {
      $form['revision_log_message']['widget'][0]['value']['#default_value'] = '';
    }

    $entity_id = $this->config('gdpr_consent.data_policy')->get('entity_id');
    $is_new = empty($entity_id);

    $form['active_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#description' => $this->t('When this field is checked then after submitting the form will be creating revision which will marked as active.'),
      '#default_value' => $is_new,
      '#disabled' => $is_new,
      '#weight' => 10,
    ];

    $form['new_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => TRUE,
      '#disabled' => TRUE,
      '#weight' => 10,
    ];

    if (isset($form['langcode'])) {
      $form['langcode']['widget'][0]['value']['#languages'] = LanguageInterface::STATE_CONFIGURABLE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $entity->setNewRevision();

    $config = $this->configFactory()->getEditable('gdpr_consent.data_policy');

    if (!empty($form_state->getValue('active_revision'))) {
      $ids = $config->get('revision_ids');
      $ids[$entity->getRevisionId()] = TRUE;
      $config->set('revision_ids', $ids)->save();
    }
    else {
      $entity->isDefaultRevision(FALSE);
    }

    $entity->setRevisionCreationTime($this->time->getRequestTime());
    $entity->setRevisionUserId($this->currentUser()->id());

    $entity->save();

    if (empty($config->get('entity_id'))) {
      $config->set('entity_id', $entity->id())->save();
    }

    $this->messenger()->addStatus($this->t('Created new revision.'));

    $form_state->setRedirect('entity.data_policy.version_history');
  }

  /**
   * Get status of clearing revision log message.
   *
   * @return bool
   *   TRUE if the message should be cleared.
   */
  public function clearMessage() {
    return TRUE;
  }

}
