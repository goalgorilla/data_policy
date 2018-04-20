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

    if ($this->getEntity()->isNew()) {
      $entity_id = $this->config('gdpr_consent.data_policy')->get('entity_id');

      $entity = $this->entityTypeManager->getStorage('data_policy')
        ->load($entity_id);

      $this->setEntity($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['revision_log_message']['widget'][0]['value']['#default_value'] = '';

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
  public function buildEntity(array $form, FormStateInterface $form_state) {
    if (!$form_state->hasValue('langcode')) {
      return parent::buildEntity($form, $form_state);
    }

    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $entity */
    $entity = $this->getEntity();

    $langcode = $form_state->getValue('langcode')[0]['value'];

    if ($entity->langcode->value != $langcode) {
      $entity = $entity->getTranslation($langcode);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $entity->setNewRevision();
    $entity->setRevisionCreationTime($this->time->getRequestTime());
    $entity->setRevisionUserId($this->currentUser()->id());

    $entity->save();

    $this->messenger()->addStatus($this->t('Created new revision.'));

    $form_state->setRedirect('entity.data_policy.version_history');
  }

}
