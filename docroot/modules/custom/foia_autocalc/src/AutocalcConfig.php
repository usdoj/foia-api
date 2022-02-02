<?php

namespace Drupal\foia_autocalc;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Provides a helper service for the autocalc configuration.
 */
class AutocalcConfig implements AutocalcConfigInterface {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * UUID generation service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * The field configuration is being added for.
   *
   * @var \Drupal\field\FieldConfigInterface
   */
  protected $fieldConfig = NULL;

  /**
   * The number field options.
   *
   * @var array
   */
  protected $numberFieldOptions = NULL;

  /**
   * Constructs a new AutocalcConfig object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   Entity type bundle info.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The UUID generation service.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, UuidInterface $uuid) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigForm(FormStateInterface $form_state, FieldConfigInterface $field_config) {
    $this->fieldConfig = $field_config;
    $field_config_id = $this->fieldConfig->id();

    $form['description'] = [
      '#type' => 'item',
      '#title' => t('How to use'),
      '#description' => t('Add the numeric fields from this content type that you would like to be used for automatic calculation of this field. Leave the field blank to remove its row. If "This entity" is checked only fields from within this entity will be used.'),
    ];

    $form['autocalc_config'] = [
      '#type' => 'table',
      '#header' => [
        'field' => t('Field'),
        'this_entity' => t('This entity'),
        'weight' => t('Weight'),
      ],
      '#empty' => t('This field is currently not being automatically calculated.'),
      '#prefix' => '<div id="autocalc-config">',
      '#suffix' => '</div>',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'weight',
        ],
      ],
    ];

    $autocalc_settings = $this->fieldConfig->getThirdPartySettings('foia_autocalc');
    $num_existing_rows = 0;
    if (isset($autocalc_settings['autocalc_settings']['autocalc_config']) && $autocalc_settings['autocalc_settings']['autocalc_config']) {
      $num_existing_rows = count($autocalc_settings['autocalc_settings']['autocalc_config']);
      foreach ($autocalc_settings['autocalc_settings']['autocalc_config'] as $uuid => $row_values) {
        $config_info = [
          'field_config' => $field_config_id,
          'uuid' => $uuid,
        ];
        $form['autocalc_config'][$uuid] = $this->buildRow($row_values, $config_info);
      }
    }

    // Keep track of new rows added.
    $num_autocalc_rows = $form_state->get('num_autocalc_rows');
    if (is_null($num_autocalc_rows)) {
      $num_autocalc_rows = $num_existing_rows;
      $form_state->set('num_autocalc_rows', $num_autocalc_rows);
    }

    // Add new rows as needed.
    for ($i = $num_existing_rows; $i < $num_autocalc_rows; $i++) {
      $uuid = $this->uuid->generate();
      $config_info = [
        'field_config' => $field_config_id,
        'uuid' => $uuid,
      ];
      $form['autocalc_config'][$uuid] = $this->buildRow(['weight' => 99 + $i], $config_info);
    }

    $form['add_autocalc_field'] = [
      '#type' => 'submit',
      '#value' => t('Add field'),
      '#submit' => ['_foia_autocalc_config_add_row'],
      '#ajax' => [
        'callback' => '_foia_autocalc_config_add_row_ajax_callback',
        'wrapper' => 'autocalc-config',
      ],
    ];

    // $this->getNumberFieldOptions($this->fieldConfig);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberFieldOptions(FieldConfigInterface $field_config) {
    if (is_null($this->numberFieldOptions)) {
      $this->numberFieldOptions = $this->buildNumberFieldOptions($field_config);
    }
    return $this->numberFieldOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function getAutocalcSettings(array $field_definitions) {
    $autocalc_settings = [];

    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_definition instanceof FieldConfigInterface) {
        if (in_array($field_definition->getType(),
        [
          'entity_reference',
          'entity_reference_revisions',
        ])) {
          $target_type = $field_definition->getSetting('target_type');
          $handler_settings = $field_definition->getSetting('handler_settings');
          if ($target_type && $handler_settings && isset($handler_settings['target_bundles'])) {
            foreach ($handler_settings['target_bundles'] as $target_bundle) {
              $entity_type_subfields = $this->entityFieldManager->getFieldDefinitions($target_type, $target_bundle);
              foreach ($entity_type_subfields as $subfield_name => $subfield_definition) {
                if ($subfield_definition instanceof FieldConfigInterface) {
                  $subfield_settings = $this->autocalcFieldSettings($subfield_name, $subfield_definition);
                  if ($subfield_settings) {
                    foreach ($subfield_settings[$subfield_name] as $settings) {
                      $subfield_formatted_settings = [
                        'field' => $field_name,
                        'weight' => $settings['weight'],
                        'this_entity' => $settings['this_entity'],
                      ];
                      unset($settings['weight']);
                      unset($settings['this_entity']);
                      $subfield_formatted_settings['subfield'] = $settings;
                      $autocalc_settings[$subfield_name][] = $subfield_formatted_settings;
                    }
                  }
                }
              }
            }
          }
        }
        else {
          $autocalc_settings += $this->autocalcFieldSettings($field_name, $field_definition);
        }
      }
    }

    return $autocalc_settings;
  }

  /**
   * Retrieves the autocalc settings for a single field.
   *
   * @param string $field_name
   *   The field name.
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field configuration object.
   *
   * @return array
   *   The autocalc settings for the provided field.
   */
  protected function autocalcFieldSettings($field_name, FieldConfigInterface $field_config) {
    $autocalc_settings = [];
    $settings = $field_config->getThirdPartySettings('foia_autocalc', 'autocalc_settings');
    if ($settings && !empty($settings['autocalc_settings']['autocalc_config']) && count($settings['autocalc_settings']['autocalc_config'])) {
      $autocalc_settings[$field_name] = $settings['autocalc_settings']['autocalc_config'];
      usort($autocalc_settings[$field_name], function ($item1, $item2) {
        return $item1['weight'] <=> $item2['weight'];
      });
      foreach ($autocalc_settings[$field_name] as $index => $autocalc_rules) {
        $subfields = explode(':', $autocalc_rules['field']);
        $autocalc_settings[$field_name][$index]['field'] = $subfields[0];
        $autocalc_settings[$field_name][$index] += $this->processAutocalcSubfields($subfields);
      }
    }
    return $autocalc_settings;
  }

  /**
   * Recursively formats autocalc subfield settings.
   *
   * @param array $subfield_names
   *   An ordered array of subfield names.
   *
   * @return array
   *   A multi-dimensional formatted array of autocalc subfields.
   */
  protected function processAutocalcSubfields(array $subfield_names) {
    $subfields = [];
    unset($subfield_names[0]);
    if (isset($subfield_names[1])) {
      $subfields['subfield']['field'] = $subfield_names[1];
      if (count($subfield_names) > 1) {
        $subfields['subfield'] += $this->processAutocalcSubfields(array_values($subfield_names));
      }
    }
    return $subfields;
  }

  /**
   * Builds the available number fields for an autocalc field.
   *
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field configuration object.
   * @param string $prefix
   *   The prefix to apply to the field options.
   *
   * @return array
   *   Array of possible autocalc fields for the provided field.
   */
  protected function buildNumberFieldOptions(FieldConfigInterface $field_config, $prefix = '') {
    $entity_type = $field_config->getTargetEntityTypeId();

    if ($entity_type != 'node') {
      $entity_bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($entity_type));
    }
    else {
      $entity_bundles = [$field_config->getTargetBundle()];
    }

    $number_field_options = [];
    foreach ($entity_bundles as $entity_bundle) {
      // $prefix = "entity--$entity_type--$entity_bundle:";
      $entity_type_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity_bundle);
      foreach ($entity_type_fields as $field_definition) {
        if (in_array($field_definition->getType(),
        [
          'entity_reference',
          'entity_reference_revisions',
        ])) {
          $field_prefix = $prefix . $field_definition->getName() . ':';
          $number_field_options += $this->buildNumberFieldReferenceOptions($field_definition, $field_config->getName(), $field_prefix);
        }
        else {
          $number_field_options += $this->buildNumberFieldOption($field_definition, $prefix);
        }
      }
    }

    return $number_field_options;
  }

  /**
   * Builds the number fields for an autocalc field with entity references.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_config
   *   The field configuration object.
   * @param string $parent_field_name
   *   The name of the parent field of the entity reference field.
   * @param string $prefix
   *   The prefix to apply to the field options.
   *
   * @return array
   *   Array of possible autocalc fields for the provided field.
   */
  protected function buildNumberFieldReferenceOptions(FieldDefinitionInterface $field_config, $parent_field_name, $prefix = '') {
    $number_field_options = [];
    // This function is recursive: check for field type and infinite loops.
    if (in_array($field_config->getType(),
    [
      'entity_reference',
      'entity_reference_revisions',
    ]) && $parent_field_name != $field_config->getName()) {
      $target_type = $field_config->getSetting('target_type');
      $handler_settings = $field_config->getSetting('handler_settings');
      if ($target_type && $handler_settings && isset($handler_settings['target_bundles'])) {
        foreach ($handler_settings['target_bundles'] as $target_bundle) {
          $entity_type_subfields = $this->entityFieldManager->getFieldDefinitions($target_type, $target_bundle);
          foreach ($entity_type_subfields as $subfield_name => $subfield_definition) {
            $number_field_options += $this->buildNumberFieldReferenceOptions($subfield_definition, $field_config->getName(), $prefix);
          }
        }
      }
    }
    else {
      $number_field_options += $this->buildNumberFieldOption($field_config, $prefix);
    }

    return $number_field_options;
  }

  /**
   * Builds the option for a single number field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $prefix
   *   The prefix apply to the field option.
   *
   * @return string
   *   A formatted string for the option for the provided field.
   */
  protected function buildNumberFieldOption(FieldDefinitionInterface $field_definition, $prefix = '') {
    $number_field_option = [];
    $field_type = $field_definition->getType();
    if (in_array($field_type, ['integer', 'decimal', 'float'])) {
      $name = $field_definition->getName();
      $number_field_option[$name] = $prefix . $name;
    }
    return $number_field_option;
  }

  /**
   * Builds a row for the autocalc config form table.
   *
   * @param array $row_values
   *   The values of the existing row.
   * @param array $config_info
   *   Configuration data for the autocalc settings.
   */
  protected function buildRow(array $row_values, array $config_info) {
    $row = [];
    $row['field'] = [
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'foia_autocalc.config.field_autocomplete',
      '#autocomplete_route_parameters' => [
        'field_config' => $config_info['field_config'],
      ],
      '#default_value' => isset($row_values['field']) ? $row_values['field'] : NULL,
      '#validated' => TRUE,
    ];
    $row['this_entity'] = [
      '#type' => 'checkbox',
      '#default_value' => isset($row_values['this_entity']) ? $row_values['this_entity'] : FALSE,
    ];
    $row['weight'] = [
      '#type' => 'weight',
      '#title' => t('Weight for this field'),
      '#title_display' => 'invisible',
      '#default_value' => isset($row_values['weight']) ? $row_values['weight'] : 99,
      '#attributes' => ['class' => ['weight']],
    ];
    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = isset($row_values['weight']) ? $row_values['weight'] : 99;
    return $row;
  }

}
