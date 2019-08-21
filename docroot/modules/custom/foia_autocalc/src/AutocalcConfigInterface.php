<?php

namespace Drupal\foia_autocalc;

use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;

/**
 * Defines an interface for the autocalc config service.
 */
interface AutocalcConfigInterface {

  /**
   * Builds a field configuration form for autocalc fields.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field configuration object.
   *
   * @return array
   *   Render array for autocalc field settings.
   */
  public function buildConfigForm(FormStateInterface $form_state, FieldConfigInterface $field_config);

  /**
   * Fetches the field ID options for an autocalc field.
   *
   * @param \Drupal\field\FieldConfigInterface $field_config
   *   The field configuration object.
   *
   * @return array
   *   An array containing the available numeric field IDs to reference.
   */
  public function getNumberFieldOptions(FieldConfigInterface $field_config);

  /**
   * Retrieves and formats the autocalc settings for the provided fields.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The field definitions to retrieve settings for.
   *
   * @return array
   *   Associative multi-dimensional array containing autocalc settings.
   */
  public function getAutocalcSettings(array $field_definitions);

}
