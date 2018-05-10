<?php

namespace Drupal\gdpr_consent\Plugin\views\filter;

use Drupal\gdpr_consent\Entity\UserConsentInterface;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Simple filter to handle matching of multiple user consent states.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_consent_state")
 */
class UserConsentState extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueOptions = [
      UserConsentInterface::STATE_AGREE => $this->t('Agree'),
      UserConsentInterface::STATE_NOT_AGREE => $this->t('Not agree'),
      UserConsentInterface::STATE_UNDECIDED => $this->t('Undesided'),
    ];

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function exposedTranslate(&$form, $type) {
    $form['#no_convert'] = TRUE;

    parent::exposedTranslate($form, $type);
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    foreach ($this->value as $key => $value) {
      if (!is_string($value)) {
        unset($this->value[$key]);
      }
    }

    parent::opSimple();
  }

}