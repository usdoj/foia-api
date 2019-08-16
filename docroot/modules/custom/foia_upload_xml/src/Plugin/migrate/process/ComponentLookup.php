<?php

namespace Drupal\foia_upload_xml\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Look up a node by Agency and Component abbreviations.
 *
 * This is similar to the entity_lookup process plugin, but requires a match on
 * two fields.
 *
 * Example:
 *
 * @code
 * process:
 *   nid:
 *     plugin: foia_component_lookup
 *     source:
 *       - agency
 *       - abbreviation
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "foia_component_lookup",
 *   handle_multiples = TRUE
 * )
 */
class ComponentLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    list($agency, $component) = $value;
    $nid = $this->query($agency, $component);
    if (empty($nid)) {
      return NULL;
    }
    return $nid;
  }

  /**
   * Look up an Agency Component node by Agency and Component abbreviations.
   *
   * @param string $agency
   *   The agency abbreviation.
   * @param string $component
   *   The component abbreviation.
   *
   * @return int|null
   *   Entity id if the queried entity exists. Otherwise NULL.
   */
  protected function query($agency, $component) {
    // First find the Agency taxonomy term corresponding to $agency.
    $taxonomy_query = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('vid', 'agency')
      ->condition('field_agency_abbreviation', $agency);
    $tids = $taxonomy_query->execute();

    if (empty($tids)) {
      return NULL;
    }

    $node_query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'agency_component')
      ->condition('field_agency', reset($tids))
      ->condition('field_agency_comp_abbreviation', $component);
    $nids = $node_query->execute();

    return $nids ? reset($nids) : NULL;
  }

}
