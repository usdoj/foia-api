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
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\node\Entity\Node;
use Drupal\time_field\Time;


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
   * Callback for `api/cfo/council` API method returns JSON Response.
   *
   * Includes all elements for the council page including attached committees
   * and details of all CFO Meetings.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON for the CFO council page.
   */
  public function getCouncil(): CacheableJsonResponse {

    // Initialize the response.
    $response = [];

    // Array to hold cache dependent node id's.
    $cache_nids = [];

    // Wrap Query in render context.
    $context = new RenderContext();
    $council_nids = \Drupal::service('renderer')->executeInRenderContext($context, function () {
      $council_query = \Drupal::entityQuery('node')
        ->condition('type', 'cfo_council')
        ->condition('status', 1)
        ->sort('created')
        ->range(0, 1);
      return $council_query->execute();
    });

    // Should only be one result.
    if (!empty($council_nids)) {

      // Grab the node id of the council node.
      $council_nid = array_shift($council_nids);

      // Add the node id of the council page.
      $cache_nids[] = 'node:' . $council_nid;

      // Load the council node.
      $council_node = $this->nodeStorage->load($council_nid);

      // Title and body of the Council node.
      $response['title'] = $council_node->label();

      if ($council_node->get('body')) {
        $body = \Drupal::service('foia_cfo.default')->absolutePathFormatter($council_node->get('body')->getValue()[0]['value']);
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
          $committee = ['committee_title' => $committee_node->label()];
          if (!empty($committee_node->body->getValue())) {
            $committee_body = \Drupal::service('foia_cfo.default')->absolutePathFormatter($committee_node->body->getValue()[0]['value']);
            $committee['committee_body'] = $committee_body;
          }
          $response['committees'][] = $committee;
        }
      }

    }

    // Wrap Query in render context.
    $context_meetings = new RenderContext();
    $meetings_nids = \Drupal::service('renderer')->executeInRenderContext($context_meetings, function () {
      // Query for all CFO meetings.
      $meetings_query = \Drupal::entityQuery('node')
        ->condition('type', 'cfo_meeting')
        ->condition('status', 1)
        ->sort('field_meeting_date', 'DESC');
      return $meetings_query->execute();
    });

    if (!empty($meetings_nids)) {

      // Store meetings as array elements.
      $response['meetings'] = [];

      // Loop through all meetings.
      foreach ($meetings_nids as $meeting_nid) {

        // Initialize this meeting.
        $meeting = [];

        // Add the node id of the meeting.
        $cache_nids[] = 'node:' . $meeting_nid;

        // Load the meeting node.
        $meeting_node = $this->nodeStorage->load($meeting_nid);

        // Add title and body for the meeting.
        $meeting['meeting_title'] = $meeting_node->label();
        if (!empty($meeting_node->body->getValue()[0]['value'])) {
          $meeting_body = \Drupal::service('foia_cfo.default')->absolutePathFormatter($meeting_node->body->getValue()[0]['value']);
          $meeting['meeting_body'] = $meeting_body;
        }

        // Add link to the Agenda if there is one.
        if (!empty($meeting_node->get('field_meeting_agenda'))) {
          /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $agenda */
          $agenda = $meeting_node->get('field_meeting_agenda');
        }

        // Meeting materials.
        if ($meeting_node->field_meeting_materials->count()) {
          // Use the Service to get info for an annual data report form.
          $meeting['meeting_materials'] = \Drupal::service('foia_cfo.default')->linkOrFileFormatter($meeting_node->field_meeting_materials);
        }

        // Meeting documents.
        if ($meeting_node->field_meeting_documents->count()) {
          $meeting['meeting_documents'] = \Drupal::service('foia_cfo.default')->linkOrFileFormatter($meeting_node->field_meeting_documents);
        }

        // Add this meeting to the return meeting array.
        $response['meetings'][] = $meeting;

      }

    }

    // Set up the Cache Meta.
    $cacheMeta = (new CacheableMetadata())
      ->setCacheTags($cache_nids)
      ->setCacheMaxAge(Cache::PERMANENT);

    // Set the JSON response from our response of council data.
    $json_response = new CacheableJsonResponse($response);

    // Add in the cache dependencies.
    $json_response->addCacheableDependency($cacheMeta);

    // Handle any bubbled cacheability metadata.
    if (!$context->isEmpty()) {
      $bubbleable_metadata = $context->pop();
      BubbleableMetadata::createFromObject($council_nids)
        ->merge($bubbleable_metadata);
    }
    if (!$context_meetings->isEmpty()) {
      $bubbleable_metadata = $context_meetings->pop();
      BubbleableMetadata::createFromObject($meetings_nids)
        ->merge($bubbleable_metadata);
    }

    // Return JSON Response.
    return $json_response;

  }

  /**
   * Callback for `api/cfo/committees` API method returns JSON Response.
   *
   * Returns array of node ids, committee name, and a few other details.
   * The node id's can then be passed to the committee detail callback
   * for full details of the committee.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON for the committees list.
   */
  public function getCommittees(): CacheableJsonResponse {

    // Initialize the response.
    $response = [];

    // Array to hold cache dependent node id's.
    $cache_nids = [];

    // Wrap Query in render context.
    $context = new RenderContext();
    $committee_nids = \Drupal::service('renderer')->executeInRenderContext($context, function () {
      $committee_query = \Drupal::entityQuery('node')
        ->condition('type', 'cfo_committee')
        ->condition('status', 1)
        ->sort('created');
      return $committee_query->execute();
    });

    if (!empty($committee_nids)) {

      // Loop through all committees.
      foreach ($committee_nids as $committee_nid) {

        // Add the node id of the committee.
        $cache_nids[] = 'node:' . $committee_nid;

        // Load the committee node.
        if ($committee_node = $this->nodeStorage->load($committee_nid)) {
          $committee = [
            'committee_nid' => $committee_nid,
            'committee_title' => $committee_node->label(),
            'committee_updated' => $committee_node->changed->value,
          ];
          $response[] = $committee;
        }

      }

    }

    // Set up the Cache Meta.
    $cacheMeta = (new CacheableMetadata())
      ->setCacheTags($cache_nids)
      ->setCacheMaxAge(Cache::PERMANENT);

    // Set the JSON response to the response of committees.
    $json_response = new CacheableJsonResponse($response);

    // Add in the cache dependencies.
    $json_response->addCacheableDependency($cacheMeta);

    // Handle any bubbled cacheability metadata.
    if (!$context->isEmpty()) {
      $bubbleable_metadata = $context->pop();
      BubbleableMetadata::createFromObject($committee_nids)
        ->merge($bubbleable_metadata);
    }

    // Return JSON Response.
    return $json_response;

  }

  /**
   * Callback for `api/cfo/committee/{committee}` API method.
   *
   * Returns JSON Response full details for a committee based on node id passed.
   *
   * @param \Drupal\node\Entity\Node $committee
   *   Node object of the committee passed as argument through routing.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse|false
   *   Returns json object or false if the node did not load.
   */
  public function getCommittee(Node $committee) {

    if (!empty($committee) && $committee->isPublished()) {

      // Array to hold cache dependent node id's (just this one).
      $cache_nids = ['node:' . $committee->id()];

      // Initialize the response with basic info.
      $response = [
        'committee_title' => $committee->label(),
        'committee_updated' => $committee->changed->value,
      ];

      // Add body HTML if any - use absolute links.
      if ($committee->hasField('body') && !empty($committee->get('body'))) {
        $response['committee_body'] = \Drupal::service('foia_cfo.default')->absolutePathFormatter($committee->get('body')->getValue()[0]['value']);
      }

      // Set up the Cache Meta.
      $cacheMeta = (new CacheableMetadata())
        ->setCacheTags($cache_nids)
        ->setCacheMaxAge(Cache::PERMANENT);

      // Set the JSON response to the response of committee data.
      $json_response = new CacheableJsonResponse($response);

      // Add in the cache dependencies.
      $json_response->addCacheableDependency($cacheMeta);

      // Return JSON Response.
      return $json_response;

    }

    else {

      // Not a valid committee or not published.
      return FALSE;

    }

  }

  /**
   * Callback for `api/cfo/meetings` API method returns JSON Response.
   *
   * Returns array of node ids, meeting name, and a few other details.
   * The node id's can then be passed to the meeting detail callback
   * for full details of the meeting.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   JSON for the meetings list.
   */
  public function getMeetings(): CacheableJsonResponse {

    // Initialize the response.
    $response = [];

    // Array to hold cache dependent node id's.
    $cache_nids = [];

    // Wrap Query in render context.
    $context = new RenderContext();
    $meeting_nids = \Drupal::service('renderer')->executeInRenderContext($context, function () {
      $meeting_query = \Drupal::entityQuery('node')
        ->condition('type', 'cfo_meeting')
        ->condition('status', 1)
        ->sort('created');
      return $meeting_query->execute();
    });

    if (!empty($meeting_nids)) {

      // Loop through all meetings.
      foreach ($meeting_nids as $meeting_nid) {

        // Add the node id of the meeting.
        $cache_nids[] = 'node:' . $meeting_nid;

        // Load the meeting node.
        if ($meeting_node = $this->nodeStorage->load($meeting_nid)) {
          $meeting = [
            'meeting_nid' => $meeting_nid,
            'meeting_title' => $meeting_node->label(),
            'meeting_updated' => $meeting_node->changed->value,
          ];
          $response[] = $meeting;
        }

      }

    }

    // Set up the Cache Meta.
    $cacheMeta = (new CacheableMetadata())
      ->setCacheTags($cache_nids)
      ->setCacheMaxAge(Cache::PERMANENT);

    // Set the JSON response to the response of meetings.
    $json_response = new CacheableJsonResponse($response);

    // Add in the cache dependencies.
    $json_response->addCacheableDependency($cacheMeta);

    // Handle any bubbled cacheability metadata.
    if (!$context->isEmpty()) {
      $bubbleable_metadata = $context->pop();
      BubbleableMetadata::createFromObject($meeting_nids)
        ->merge($bubbleable_metadata);
    }

    // Return JSON Response.
    return $json_response;

  }

  /**
   * Callback for `api/cfo/meeting/{meeting}` API method.
   *
   * Returns JSON Response full details for a meeting based on node id passed.
   *
   * @param \Drupal\node\Entity\Node $meeting
   *   Node object of the meeting passed as argument through routing.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse|false
   *   Returns json object or false if the node did not load.
   */
  public function getMeeting(Node $meeting) {

    if (!empty($meeting) && $meeting->isPublished()) {

      // Array to hold cache dependent node id's (just this one).
      $cache_nids = ['node:' . $meeting->id()];

      // Initialize the response with basic info.
      $response = [
        'meeting_title' => $meeting->label(),
        'meeting_updated' => $meeting->changed->value,
      ];

      // Add body HTML if any - use absolute links.
      if ($meeting->hasField('body') && !empty($meeting->get('body'))) {
        $response['meeting_body'] = \Drupal::service('foia_cfo.default')->absolutePathFormatter($meeting->get('body')->getValue()[0]['value']);
      }

      // Heading.
      if ($meeting->hasField('field_meeting_heading') && !empty($meeting->get('field_meeting_heading'))) {
        $response['meeting_heading'] = \Drupal::service('foia_cfo.default')->absolutePathFormatter($meeting->get('field_meeting_heading')->getValue()[0]['value']);
      }

      // Agenda.
      if ($meeting->hasField('field_meeting_agenda') && !empty($meeting->get('field_meeting_agenda'))) {

        // Initialize agenda - to hold agenda items.
        $agenda = [];

        // Loop over the referenced paragraph entities.
        foreach ($meeting->get('field_meeting_agenda')->referencedEntities() as $item) {

          // Initialize this agenda item.
          $agenda_item = [];

          // Agenda Time.
          if (!empty($item->get('field_agenda_item_time'))) {
            if (!empty($item->get('field_agenda_item_time')->getValue()[0]['value'])) {
              $time_object = Time::createFromTimestamp($item->get('field_agenda_item_time')->getValue()[0]['value']);
              $agenda_item['agenda_time'] = $time_object->format();
            }
          }

          // Agenda Title.
          if (!empty($item->get('field_agenda_item_title'))) {
            if (!empty($item->get('field_agenda_item_title')->getValue()[0]['value'])) {
              $agenda_item['agenda_title'] = $item->get('field_agenda_item_title')->getValue()[0]['value'];
            }
          }

          // Agenda Description.
          if (!empty($item->get('field_agenda_item_description'))) {
            if (!empty($item->get('field_agenda_item_description')->getValue()[0]['value'])) {
              $agenda_item['agenda_description'] = $item->get('field_agenda_item_description')->getValue()[0]['value'];
            }
          }

          // Add agenda item to agenda array.
          if (!empty($agenda_item)) {
            $agenda[] = $agenda_item;
          }

        }

        // Add agenda to the meeting array.
        if (!empty($agenda)) {
          $response['meeting_agenda'] = $agenda;
        }

      }

      // Set up the Cache Meta.
      $cacheMeta = (new CacheableMetadata())
        ->setCacheTags($cache_nids)
        ->setCacheMaxAge(Cache::PERMANENT);

      // Set the JSON response to the response of meeting data.
      $json_response = new CacheableJsonResponse($response);

      // Add in the cache dependencies.
      $json_response->addCacheableDependency($cacheMeta);

      // Return JSON Response.
      return $json_response;

    }

    else {

      // Not a valid meeting or not published.
      return FALSE;

    }

  }

}
