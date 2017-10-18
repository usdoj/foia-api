<?php

namespace Drupal\foia_request\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the FOIA Request entity.
 *
 * @ingroup foia_request
 *
 * @ContentEntityType(
 *   id = "foia_request",
 *   label = @Translation("FOIA Request"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\foia_request\FoiaRequestListBuilder",
 *     "views_data" = "Drupal\foia_request\Entity\FoiaRequestViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\foia_request\Form\FoiaRequestForm",
 *       "add" = "Drupal\foia_request\Form\FoiaRequestForm",
 *       "edit" = "Drupal\foia_request\Form\FoiaRequestForm",
 *       "delete" = "Drupal\foia_request\Form\FoiaRequestDeleteForm",
 *     },
 *     "access" = "Drupal\foia_request\FoiaRequestAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\foia_request\FoiaRequestHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "foia_request",
 *   admin_permission = "administer foia request entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/foia_request/{foia_request}",
 *     "add-form" = "/admin/structure/foia_request/add",
 *     "edit-form" = "/admin/structure/foia_request/{foia_request}/edit",
 *     "delete-form" = "/admin/structure/foia_request/{foia_request}/delete",
 *     "collection" = "/admin/structure/foia_request",
 *   },
 *   field_ui_base_route = "foia_request.settings"
 * )
 */
class FoiaRequest extends ContentEntityBase implements FoiaRequestInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestStatus() {
    $this->get('request_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestStatus($requestStatus) {
    if (!in_array($requestStatus, self::getValidRequestStatuses())) {
      $requestStatus = FoiaRequestInterface::STATUS_QUEUED;
    }
    $this->set('request_status', $requestStatus);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getValidRequestStatuses() {
    return [
      FoiaRequestInterface::STATUS_QUEUED,
      FoiaRequestInterface::STATUS_SUBMITTED,
      FoiaRequestInterface::STATUS_FAILED,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the FOIA Request entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $requestStatusOptions = [
      FoiaRequestInterface::STATUS_QUEUED => 'Queued for submission',
      FoiaRequestInterface::STATUS_SUBMITTED => 'Submitted to component',
      FoiaRequestInterface::STATUS_FAILED => 'Failed submission to component',
    ];
    $fields['request_status'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Request Status'))
      ->setDescription(t('The status of the FOIA Request.'))
      ->setDefaultValue(FoiaRequestInterface::STATUS_QUEUED)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'list_default',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('allowed_values', $requestStatusOptions);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the FOIA Request is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
