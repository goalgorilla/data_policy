<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the InformBlock add and edit forms.
 */
class InformBlockForm extends EntityForm {

  /**
   * Constructs an InformBlockForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $informblock = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $informblock->label(),
      '#description' => $this->t('Indicate what will be explained on this page.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $informblock->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$informblock->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => isset($informblock->status) ? $informblock->status : TRUE,
      '#description' => $this->t('Whether this is on or off.'),
    ];

    $form['summary'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Summary'),
      '#default_value' => $informblock->summary,
      '#required' => TRUE,
      '#description' => $this->t('Summarise what data is collected.'),
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => $informblock->body,
      '#required' => FALSE,
      '#description' => $this->t('Describe in detail what data is collected and how it is used.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $informblock = $this->entity;
    $status = $informblock->save();

    if ($status) {
      drupal_set_message($this->t('Saved the %label Example.', [
        '%label' => $informblock->label(),
      ]));
    }
    else {
      drupal_set_message($this->t('The %label Example was not saved.', [
        '%label' => $informblock->label(),
      ]));
    }

    $form_state->setRedirect('entity.informblock.collection');
  }

  /**
   * Helper function to check whether an InformBlock configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('informblock')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}