<?php

namespace Drupal\foia_cfo\Controller;

/**
 * @file
 * Contains \Drupal\foia_cfo\Controller\TestAPIController.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\views\Views;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;

/**
 * Controller routines for foia_cfo routes.
 */
class CFOController extends ControllerBase {

  /**
   * Node Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $nodeStorage;

  /**
   * Constructor for this class.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct() {
    $this->nodeStorage = \Drupal::entityTypeManager()->getStorage('node');
  }

  /**
   * Callback for `api/cfo/council` API method.
   */
  public function getCouncil(): CacheableJsonResponse {

    // Initialize the response.
    $response = [];

    // Array to hold cache dependent node id's.
    $cache_nids = [];

    // Load the council node.  Load the oldest one, there should be just one.
    $council_query = \Drupal::entityQuery('node')
      ->condition('type', 'cfo_council')
      ->condition('status', 1)
      ->sort('created')
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();

    // Should only be one result.
    if (!empty($council_query)) {

      // Grab the node id of the council node.
      $council_nid = array_shift($council_query);

      // Add the node id of the council page.
      $cache_nids[] = 'node:' . $council_nid;

      // Load the council node.
      $council_node = $this->nodeStorage->load($council_nid);

      // Title and body of the Council node.
      $response['title'] = $council_node->label();

      if ($council_node->get('body')) {
        $body = self::absolutePathFormatter($council_node->get('body')->getValue()[0]['value']);
        $response['body'] = $body;
      }

      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $committees */
      $committees = $council_node->get('field_council_committees');

      if (!empty($committees)) {

        // Store committees as array elements.
        $response['committees'] = [];

        // Loop through the committees and add title and body to the feed.
        foreach ($committees as $committee) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $committee */
          $nid = $committee->getValue()['target_id'];
          // Add the node id of the committee page.
          $cache_nids[] = 'node:' . $nid;
          $committee_node = $this->nodeStorage->load($nid);
          $committee_body = self::absolutePathFormatter($committee_node->body->getValue()[0]['value']);
          $response['committees'][] = [
            'committee_title' => $committee_node->label(),
            'committee_body' => $committee_body,
          ];
        }
      }

    }

    // Query for all CFO meetings.
    $meetings_query = \Drupal::entityQuery('node')
      ->condition('type', 'cfo_meeting')
      ->condition('status', 1)
      ->sort('field_meeting_date', 'DESC')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($meetings_query)) {

      // Store meetings as array elements.
      $response['meetings'] = [];

      // Loop through all meetings.
      foreach ($meetings_query as $meeting_nid) {

        // Initialize this meeting.
        $meeting = [];

        // Add the node id of the meeting.
        $cache_nids[] = 'node:' . $meeting_nid;

        // Load the meeting node.
        $meeting_node = $this->nodeStorage->load($meeting_nid);

        // Add title and body for the meeting.
        $meeting['meeting_title'] = $meeting_node->label();
        if (!empty($meeting_node->body->getValue()[0]['value'])) {
          $meeting_body = self::absolutePathFormatter($meeting_node->body->getValue()[0]['value']);
          $meeting['meeting_body'] = $meeting_body;
        }

        // Add link to the Agenda if there is one.
        if (!empty($meeting_node->get('field_meeting_agenda'))) {
          /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $agenda */
          $agenda = $meeting_node->get('field_meeting_agenda');
        }

        // Meeting materials.
        if ($meeting_node->field_meeting_materials->count()) {
          $meeting['meeting_materials'] = self::linkOrFileFormatter($meeting_node->field_meeting_materials);
        }

        // Meeting documents.
        if ($meeting_node->field_meeting_documents->count()) {
          $meeting['meeting_documents'] = self::linkOrFileFormatter($meeting_node->field_meeting_documents);
        }

        // Add this meeting to the return meeting array.
        $response['meetings'][] = $meeting;

      }

    }

    $cacheMeta = (new CacheableMetadata())
      ->setCacheTags($cache_nids)
      ->setCacheMaxAge(Cache::PERMANENT);

    // Set the JSON response to the agents array.
    $json_response = new CacheableJsonResponse($response);

    // Add in the cache dependencies.
    $json_response->addCacheableDependency($cacheMeta);

    // Return JSON Response.
    return $json_response;

  }

  /**
   * Adds the absolute path to src and href paramater values.
   *
   * @param string $input
   *   Input string (html).
   *
   * @return string
   *   Input string with absolute paths to src and href.
   */
  private function absolutePathFormatter(string $input): string {

    // Grab the "base href" with http.
    $host = \Drupal::request()->getSchemeAndHttpHost();

    // Replacements array - look for href and src with relative paths.
    $replacements = [
      'href="/' => 'href="' . $host . '/',
      'src="/' => 'src="' . $host . '/',
    ];

    // Add absolute references to relative paths.
    return str_replace(array_keys($replacements), array_values($replacements), $input);

  }

  /**
   * Formats "Link or File" paragraph types.
   *
   * @param \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $field
   *   The field.
   *
   * @return array
   *   Labels and links to either the url or the file.
   */
  private function linkOrFileFormatter(EntityReferenceRevisionsFieldItemList $field): array {

    // Initialize return array.
    $return = [];

    // Loop over the referenced paragraph entities.
    foreach ($field->referencedEntities() as $item) {

      // Initialize this item.
      $return_item = [];

      // Set the item label.
      $return_item['item_title'] = $item->get('field_link_label')
        ->getValue()[0]['value'];

      // Set the item link - this will be a URL or File.
      if (!empty($item->get('field_link_link')->getValue()[0]['uri'])) {
        $link = $item->get('field_link_link')
          ->first()
          ->getUrl()
          ->setAbsolute(TRUE)
          ->toString(TRUE)
          ->getGeneratedUrl();
        $return_item['item_link'] = $link;
      }
      elseif (!empty($item->get('field_link_file')
        ->getValue()[0]['target_id'])) {
        $fid = $item->get('field_link_file')->getValue()[0]['target_id'];
        $file = File::load($fid);
        $return_item['item_link'] = $file->createFileUrl(FALSE);
      }

      // Add this item to the return array.
      $return[] = $return_item;

    }

    // Returns array of items with labels and links.
    return $return;

  }

}
