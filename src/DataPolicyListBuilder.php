<?php

namespace Drupal\gdpr_consent;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Data policy entities.
 *
 * @ingroup gdpr_consent
 */
class DataPolicyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Data policy ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\gdpr_consent\Entity\DataPolicy */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.data_policy.edit_form',
      ['data_policy' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
