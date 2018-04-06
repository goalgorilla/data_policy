<?php

namespace Drupal\gdpr_consent\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\gdpr_consent\InformBlockInterface;

/**
 * Defines the InformBlock entity.
 *
 * @ConfigEntityType(
 *   id = "informblock",
 *   label = @Translation("Inform Block"),
 *   handlers = {
 *     "access" = "Drupal\gdpr_consent\GdprConsentAccessControlHandler",
 *     "list_builder" = "Drupal\gdpr_consent\Controller\InformBlockListBuilder",
 *     "form" = {
 *       "add" = "Drupal\gdpr_consent\Form\InformBlockForm",
 *       "edit" = "Drupal\gdpr_consent\Form\InformBlockForm",
 *       "delete" = "Drupal\gdpr_consent\Form\InformBlockDeleteForm",
 *     }
 *   },
 *   config_prefix = "informblock",
 *   admin_permission = "administer inform and consent settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/inform-consent/{informblock}",
 *     "delete-form" = "/admin/config/system/inform-consent/{informblock}/delete",
 *   }
 * )
 */
class InformBlock extends ConfigEntityBase implements InformBlockInterface {

  /**
   * The ID of the Inform Block.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the Inform Block.
   *
   * @var string
   */
  public $title;

  /**
   * The link to a page where the Inform Block should be showed.
   *
   * @var string
   */
  public $link;

  /**
   * The status of the Inform Block.
   *
   * If it is set to FALSE it should not display any of the text.
   *
   * @var bool
   */
  public $status;

  /**
   * The summary to show in the block.
   *
   * @var string
   */
  public $summary;

  /**
   * The detailed description to show in the pop-up.
   *
   * @var string
   */
  public $body;

}
