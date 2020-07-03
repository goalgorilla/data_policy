<?php

namespace Drupal\data_policy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Data policy entities.
 *
 * @ingroup data_policy
 */
class DataPolicyListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Data policy ID');
    $header['revisions'] = $this->t('Revisions');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\data_policy\Entity\DataPolicy */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.data_policy.version_history',
      ['entity_id' => $entity->id()]
    );

    $row['id'] = $entity->id();
    $count = count($this->storage->revisionIds($entity));
    $row['revisions'] = $this->t('%count', ['%count' => $count]);

    return $row + parent::buildRow($entity);
  }

}
