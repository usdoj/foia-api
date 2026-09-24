<?php

namespace Drupal\foia_raw_data_to_report\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Adds unsaved component upload placeholders to the report form.
 */
class ComponentPlaceholders {

  /**
   * Builds an agency-scoped chooser within the existing AJAX wrapper.
   */
  public static function build(array &$form, FormStateInterface $form_state): void {
    $options = [];
    foreach (static::components($form_state) as $id => $label) {
      $label = Html::decodeEntities($label);
      $options[$id] = ['title' => ['data' => ['#title' => $label, '#plain_text' => $label]]];
    }
    $form['field_component_uploads']['placeholders'] = [
      '#type' => 'details',
      '#title' => t('Add component placeholders'),
      '#open' => TRUE,
      '#weight' => -10,
      'components' => [
        '#type' => 'tableselect',
        '#header' => ['title' => t('Agency Component')],
        '#options' => $options,
        '#empty' => t('Select an agency to see its available components.'),
        '#parents' => ['raw_data_placeholder_components'],
        '#description' => t('Select components, or use the header checkbox to select or clear all. Existing component uploads are kept; duplicates are skipped.'),
      ],
      'add' => [
        '#type' => 'submit',
        '#value' => t('Add placeholders for component data below'),
        '#name' => 'add_component_placeholders',
        '#submit' => [[static::class, 'submit']],
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => 'foia_raw_data_to_report_refresh_components',
          'wrapper' => 'raw-data-component-uploads',
        ],
      ],
      'result' => [
        '#type' => 'container',
        '#attributes' => ['role' => 'status'],
        'text' => ['#plain_text' => $form_state->get('component_placeholder_result') ?? ''],
      ],
    ];
  }

  /**
   * Returns accessible components for the currently submitted agency.
   */
  protected static function components(FormStateInterface $form_state): array {
    $node = $form_state->getFormObject()->getEntity();
    $agency = $form_state->getUserInput()['field_agency'][0]['target_id'] ?? $node->get('field_agency')->target_id;
    if (is_string($agency) && !ctype_digit($agency)) {
      $agency = EntityAutocomplete::extractEntityIdFromAutocompleteInput($agency);
    }
    $handler = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance([
      'target_type' => 'node',
      'handler' => 'raw_data_component',
      'target_bundles' => ['agency_component' => 'agency_component'],
      'agency_id' => $agency ?: 0,
      'sort' => ['field' => 'title', 'direction' => 'ASC'],
    ]);
    return $handler->getReferenceableEntities()['agency_component'] ?? [];
  }

  /**
   * Appends selected components without saving the node or paragraphs.
   */
  public static function submit(array &$form, FormStateInterface $form_state): void {
    $field = 'field_component_uploads';
    $parents = $form[$field]['widget']['#field_parents'];
    $state = WidgetBase::getWidgetState($parents, $field, $form_state);
    $input = $form_state->getUserInput();
    // Recheck membership and access on submission, including tampered input.
    $selected = array_intersect_key(static::components($form_state), array_filter($input['raw_data_placeholder_components'] ?? []));
    $empty = [];
    foreach ($state['paragraphs'] ?? [] as $delta => $item) {
      if (in_array($item['mode'], ['remove', 'removed'], TRUE)) {
        continue;
      }
      $paragraph = $item['entity'];
      $component = $input[$field][$delta]['subform']['field_agency_component'][0]['target_id'] ?? $paragraph->get('field_agency_component')->target_id;
      $has_component_input = !empty($component);
      if (is_string($component) && !ctype_digit($component)) {
        $component = EntityAutocomplete::extractEntityIdFromAutocompleteInput($component);
      }
      if ($component) {
        unset($selected[$component]);
      }
      elseif (!$has_component_input && $paragraph->get('field_request_data_csv')->isEmpty() && $paragraph->get('section_ix_xi_data')->isEmpty() && empty($input[$field][$delta]['subform']['field_request_data_csv'][0]['fids']) && empty($input[$field][$delta]['subform']['section_ix_xi_data'][0]['fids'])) {
        $empty[] = $delta;
      }
    }
    $count = count($selected);
    foreach ($selected as $id => $label) {
      if ($empty) {
        $delta = array_shift($empty);
        $state['paragraphs'][$delta]['entity']->set('field_agency_component', $id);
        // Submitted empty input would otherwise override the new default.
        unset($input[$field][$delta]['subform']['field_agency_component']);
      }
      else {
        $delta = $state['items_count']++;
        $paragraph = Paragraph::create([
          'type' => 'raw_data_component_upload',
          'field_agency_component' => $id,
        ]);
        $paragraph->setParentEntity($form_state->getFormObject()->getEntity(), $field);
        $state['paragraphs'][$delta] = [
          'entity' => $paragraph,
          'display' => EntityFormDisplay::collectRenderDisplay($paragraph, 'default'),
          'mode' => 'edit',
        ];
      }
    }
    unset($input['raw_data_placeholder_components']);
    $form_state->setUserInput($input);
    WidgetBase::setWidgetState($parents, $field, $form_state, $state);
    $form_state->set('component_placeholder_result', t('Added @count component placeholder(s). Existing uploads were preserved.', ['@count' => $count]));
    $form_state->setRebuild();
  }

}
