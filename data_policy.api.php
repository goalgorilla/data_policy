<?php

/**
 * @file
 * Documentation for Data Policy API.
 */

/**
 * Alter the data policy destination before performing the redirect.
 *
 * @param \Drupal\Core\Routing\RedirectDestinationInterface $destination
 *   The original destination parameter.
 *
 * @return \Drupal\Core\Routing\RedirectDestinationInterface
 *   An altered data policy destination url.
 */
function hook_data_policy_destination_alter(\Drupal\Core\Routing\RedirectDestinationInterface $destination) {
  return $destination;
}
