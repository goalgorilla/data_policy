<?php

namespace Drupal\gdpr_consent\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Inform Blocks.
 */
class InformBlockListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Title');
    $header['enabled'] = $this->t('Enabled');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['enabled'] = $entity->status === TRUE ? $this->t('Yes') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

}