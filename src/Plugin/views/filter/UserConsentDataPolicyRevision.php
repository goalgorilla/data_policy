<?php

namespace Drupal\gdpr_consent\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple filter to handle matching of multiple data policy revisions.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("user_consent_data_policy_revision")
 */
class UserConsentDataPolicyRevision extends InOperator {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a UserConsentDataPolicyRevision object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $connection, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $query = $this->connection->select('data_policy_revision', 'r')
      ->fields('r', ['vid', 'revision_created'])
      ->orderBy('revision_created', 'DESC');

    $query->innerJoin('user_consent', 'c', 'c.data_policy_revision_id = r.vid');
    $query->condition('status', 1);

    $this->valueOptions = $query->execute()->fetchAllKeyed();

    foreach ($this->valueOptions as &$timestamp) {
      $timestamp = $this->dateFormatter->format($timestamp);
    }

    return $this->valueOptions;
  }

}
