<?php

namespace Drupal\gdpr_consent;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Data policy entity.
 *
 * @see \Drupal\gdpr_consent\Entity\DataPolicy.
 */
class DataPolicyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = parent::access($entity, $operation, $account, $return_as_object);
    $x = 1;
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'update') {
      return AccessResult::allowedIfHasPermission($account, 'edit data policy');
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
