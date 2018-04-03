<?php

namespace Drupal\gdpr_consent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class InformBlockForm.
 *
 * @package Drupal\gdpr_consent\Form
 */
class OLDInformBlockForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'inform_block_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['gdpr_consent.inform_blocks'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $block = NULL) {
    // If there's nothing to edit throw a 404.
    if (empty($block)) {
      throw new NotFoundHttpException();
    }

    // Get the configuration file.
    $config = $this->config('gdpr_consent.inform_blocks');

    $form['id'] = [
      '#type' => 'hidden',
      '#value' => $block,
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config->get($block . '.title'),
      '#required' => TRUE,
      '#description' => $this->t('Indicate what will be explained on this page.'),
    ];

    $form['summary'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Summary'),
      '#default_value' => $config->get($block . '.summary'),
      '#required' => TRUE,
      '#description' => $this->t('Summarise what data is collected.'),
    );

    $form['body'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#default_value' => $config->get($block . '.body'),
      '#required' => FALSE,
      '#description' => $this->t('Describe in detail what data is collected and how it is used.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get the configuration file.
    $config = $this->config('gdpr_consent.inform_blocks');

    $config->set($form_state->getValue('id') . '.title', $form_state->getValue('title'))
      ->set($form_state->getValue('id') . '.summary', $form_state->getValue('summary')['value'])
      ->set($form_state->getValue('id') . '.body', $form_state->getValue('body')['value'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}