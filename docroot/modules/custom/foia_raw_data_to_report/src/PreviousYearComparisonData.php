<?php

namespace Drupal\foia_raw_data_to_report;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Retrieves prior annual report comparisons without blocking XML generation.
 */
final class PreviousYearComparisonData {

  /**
   * Paragraph and agency-overall sources for the six comparison values.
   */
  private const SOURCES = [
    'requests' => [
      'paragraph' => 'field_foia_requests_va',
      'fields' => [
        'received' => ['field_req_received_yr', 'field_overall_req_received_yr'],
        'processed' => ['field_req_processed_yr', 'field_overall_req_processed_yr'],
      ],
    ],
    'appeals' => [
      'paragraph' => 'field_admin_app_via',
      'fields' => [
        'received' => ['field_app_received_yr', 'field_overall_via_app_recd_yr'],
        'processed' => ['field_app_processed_yr', 'field_overall_via_app_proc_yr'],
      ],
    ],
    'backlog' => [
      'paragraph' => 'field_foia_xiia',
      'fields' => [
        'requests' => ['field_back_req_end_yr', 'field_overall_xiia_back_req_end_'],
        'appeals' => ['field_back_app_end_yr', 'field_overall_xiia_back_app_end_'],
      ],
    ],
  ];

  /**
   * Constructs the optional prior-report reader.
   */
  public function __construct(private EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Returns prior component/overall values and nonblocking lookup notes.
   */
  public function load(int $agency_id, int $fiscal_year, array $component_ids): array {
    $result = ['notes' => []];
    foreach (self::SOURCES as $section => $source) {
      $empty = array_fill_keys(array_keys($source['fields']), '0');
      $result[$section] = ['components' => array_fill_keys($component_ids, $empty), 'overall' => $empty];
    }
    $year = $fiscal_year - 1;
    try {
      $storage = $this->entityTypeManager->getStorage('node');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'annual_foia_report_data')
        ->condition('field_agency.target_id', $agency_id)
        ->condition('field_foia_annual_report_yr', $year)
        ->sort('nid')
        ->range(0, 2)
        ->execute();
      if (count($ids) !== 1) {
        $reason = $ids ? 'multiple matching reports were found' : 'no matching report was found';
        $result['notes'][] = sprintf('Note: Could not retrieve the previous annual report for Agency %d, fiscal year %d: %s. Last-year comparison values remain 0.', $agency_id, $year, $reason);
        return $result;
      }
      $report = $storage->load(reset($ids));
      if (!$report instanceof FieldableEntityInterface) {
        throw new \RuntimeException('The matching annual report could not be loaded.');
      }
      foreach (self::SOURCES as $section => $source) {
        foreach ($source['fields'] as $key => [$component_field, $overall_field]) {
          $result[$section]['overall'][$key] = $this->quantity($report, $overall_field, $result['notes'], "FY $year agency overall");
        }
        $matches = [];
        if ($report->hasField($source['paragraph'])) {
          foreach ($report->get($source['paragraph']) as $item) {
            $paragraph = $item->entity;
            if (!$paragraph instanceof FieldableEntityInterface || !$paragraph->hasField('field_agency_component')) {
              continue;
            }
            $id = (int) $paragraph->get('field_agency_component')->target_id;
            if (in_array($id, $component_ids, TRUE)) {
              $matches[$id][] = $paragraph;
            }
          }
        }
        foreach ($component_ids as $id) {
          if (count($matches[$id] ?? []) !== 1) {
            $result['notes'][] = sprintf('Note: FY %d annual report has missing or ambiguous %s data for Component %d. Its last-year values remain 0.', $year, $source['paragraph'], $id);
            continue;
          }
          foreach ($source['fields'] as $key => [$component_field, $overall_field]) {
            $result[$section]['components'][$id][$key] = $this->quantity($matches[$id][0], $component_field, $result['notes'], "FY $year Component $id");
          }
        }
      }
    }
    catch (\Throwable $exception) {
      $result['notes'][] = sprintf('Note: Could not fully retrieve the previous annual report for Agency %d, fiscal year %d: %s Missing last-year comparison values remain 0.', $agency_id, $year, $exception->getMessage());
    }
    return $result;
  }

  /**
   * Reads nonnegative integer quantities without losing precision to casts.
   */
  private function quantity(FieldableEntityInterface $entity, string $field, array &$notes, string $context): string {
    $value = $entity->hasField($field) ? trim((string) $entity->get($field)->value) : '';
    if (!preg_match('/^[0-9]+$/', $value)) {
      $notes[] = sprintf('Note: %s has missing or invalid %s data; its last-year comparison value remains 0.', $context, $field);
      return '0';
    }
    return ltrim($value, '0') ?: '0';
  }

}
