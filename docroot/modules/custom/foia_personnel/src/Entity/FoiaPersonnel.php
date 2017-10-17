<?php

namespace Drupal\foia_personnel\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the FOIA Personnel entity.
 *
 * @ingroup foia_personnel
 *
 * @ContentEntityType(
 *   id = "foia_personnel",
 *   label = @Translation("FOIA Personnel"),
 *   handlers = {
 *     "storage" = "Drupal\foia_personnel\FoiaPersonnelStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\foia_personnel\FoiaPersonnelListBuilder",
 *     "views_data" = "Drupal\foia_personnel\Entity\FoiaPersonnelViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\foia_personnel\Form\FoiaPersonnelForm",
 *       "add" = "Drupal\foia_personnel\Form\FoiaPersonnelForm",
 *       "edit" = "Drupal\foia_personnel\Form\FoiaPersonnelForm",
 *       "delete" = "Drupal\foia_personnel\Form\FoiaPersonnelDeleteForm",
 *     },
 *     "access" = "Drupal\foia_personnel\FoiaPersonnelAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\foia_personnel\FoiaPersonnelHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "foia_personnel",
 *   revision_table = "foia_personnel_revision",
 *   revision_data_table = "foia_personnel_field_revision",
 *   admin_permission = "administer foia personnel entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/foia_personnel/{foia_personnel}",
 *     "add-form" = "/admin/structure/foia_personnel/add",
 *     "edit-form" = "/admin/structure/foia_personnel/{foia_personnel}/edit",
 *     "delete-form" = "/admin/structure/foia_personnel/{foia_personnel}/delete",
 *     "version-history" = "/admin/structure/foia_personnel/{foia_personnel}/revisions",
 *     "revision" = "/admin/structure/foia_personnel/{foia_personnel}/revisions/{foia_personnel_revision}/view",
 *     "revision_revert" = "/admin/structure/foia_personnel/{foia_personnel}/revisions/{foia_personnel_revision}/revert",
 *     "revision_delete" = "/admin/structure/foia_personnel/{foia_personnel}/revisions/{foia_personnel_revision}/delete",
 *     "collection" = "/admin/structure/foia_personnel",
 *   },
 *   field_ui_base_route = "foia_personnel.settings"
 * )
 */
class FoiaPersonnel extends RevisionableContentEntityBase implements FoiaPersonnelInterface {

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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the foia_personnel
    // owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the FOIA Personnel entity.'))
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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the FOIA Personnel.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the FOIA Personnel is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    switch ($rel) {
      case 'revision_revert':
      case 'revision_delete':
        $uri_route_parameters['foia_personnel_revision'] = $this->getRevisionId();
        break;
    }
    return $uri_route_parameters;

  }

}
