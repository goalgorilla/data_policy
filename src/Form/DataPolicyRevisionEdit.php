<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing a Data policy revision.
 *
 * @ingroup gdpr_consent
 */
class DataPolicyRevisionEdit extends DataPolicyForm {

  /**
   * Constructs a DataPolicyRevisionEdit object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, ModuleHandlerInterface $module_handler = NULL) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);

    $this->moduleHandler = $module_handler;

    $entity_id = $this->config('gdpr_consent.data_policy')->get('entity_id');

    $this->entity = $this->entityManager->getStorage('data_policy')
      ->load($entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdpr_consent_data_policy_revision_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $data_policy_revision = NULL) {
    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $entity */
    $entity = &$this->entity;

    $entity = $this->entityManager->getStorage('data_policy')
      ->loadRevision($data_policy_revision);

    $form = parent::buildForm($form, $form_state);

    $form['active_revision']['#default_value'] = $entity->isDefaultRevision();
    $form['active_revision']['#disabled'] = $entity->isDefaultRevision();
    $form['new_revision']['#default_value'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\gdpr_consent\Entity\DataPolicyInterface $entity */
    $entity = &$this->entity;

    if (!empty($form_state->getValue('active_revision')) && !$entity->isDefaultRevision()) {
      $entity->isDefaultRevision(TRUE);
    }

    $entity->save();

    $this->messenger()->addStatus($this->t('Saved revision.'));

    $form_state->setRedirect('entity.data_policy.version_history');
  }

  /**
   * {@inheritdoc}
   */
  public function clearMessage() {
    return FALSE;
  }

}
