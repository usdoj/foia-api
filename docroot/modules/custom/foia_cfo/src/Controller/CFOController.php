<?php

namespace Drupal\foia_cfo\Controller;

/**
 * @file
 * Contains \Drupal\foia_cfo\Controller\CFOController.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\file\Entity\File;

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

    // Array to hold cache tags for this feed.
    $cache_tags = [];

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
      $cache_tags[] = 'node:' . $council_nid;

      // Load the council node.
      $council_node = $this->nodeStorage->load($council_nid);

      // Title and body of the Council node.
      $response['title'] = $council_node->label();

      if (
        !empty($council_node->get('body'))
        && !empty($council_node->get('body')->getValue()[0]['value'])
      ) {
        $body = \Drupal::service('foia_cfo.default')->absolutePathFormatter($council_node->get('body')->getValue()[0]['value']);
        $response['body'] = $body;
      }

      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $committees */
      $committees = $council_node->get('field_council_committees');

      if (!empty($committees)) {

        // If there are attached committees, need to add to cache tags.
        // Set to all nodes of this type because if a new committee is
        // added the cache will not update.
        $cache_tags[] = 'node_list:cfo_committee';

        // Store committees as array elements.
        $response['committees'] = [];

        // Loop through the committees and add title and body to the feed.
        foreach ($committees as $committee) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $committee */
          $nid = $committee->getValue()['target_id'];
          // Add the node id of the committee page.
          $committee_node = $this->nodeStorage->load($nid);
          if (!empty($committee_node)) {
            if ($committee_node->isPublished()) {
              $committee = ['committee_title' => $committee_node->label()];
              if (!empty($committee_node->body->getValue())) {
                $committee_body = \Drupal::service('foia_cfo.default')->absolutePathFormatter($committee_node->body->getValue()[0]['value']);
                $committee['committee_body'] = $committee_body;
              }

              // Add Committee attachments.
              if ( $committee_node->hasField('field_attachments') ) {
                $attachments =  $committee_node->get('field_attachments');
                $committee['committee_attachments'] = \Drupal::service('foia_cfo.default')->buildAttachmentList($attachments);
              }

              // Add working groups.
              if ($committee_node->field_working_groups->count()) {
                $committee['working_groups'] = \Drupal::service('foia_cfo.default')->workingGroupFieldFormatter($committee_node->field_working_groups);
              }

              $response['committees'][] = $committee;
            }
          }
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

      // All meetings are on this feed - cache tag to reflect that.
      $cache_tags[] = 'node_list:cfo_meeting';

      // Store meetings as array elements.
      $response['meetings'] = [];

      // Loop through all meetings.
      foreach ($meetings_nids as $meeting_nid) {

        // Initialize this meeting.
        $meeting = [];

        // Load the meeting node.
        $meeting_node = $this->nodeStorage->load($meeting_nid);

        // Add Meeting Timestamp.
        if (
          !empty($meeting_node->get('field_meeting_date'))
          && !empty($meeting_node->get('field_meeting_date')->getValue()[0]['value'])
        ) {
          $meeting['meeting_timestamp'] = strtotime($meeting_node->get('field_meeting_date')->getValue()[0]['value']);
        }

        // Add title and body for the meeting.
        $meeting['meeting_title'] = $meeting_node->label();
        if (!empty($meeting_node->body->getValue()[0]['value'])) {
          $meeting_body = \Drupal::service('foia_cfo.default')->absolutePathFormatter($meeting_node->body->getValue()[0]['value']);
          $meeting['meeting_body'] = $meeting_body;
        }

        // Meeting materials.
        if ($meeting_node->field_meeting_materials->count()) {
          // Use the Service to get info for an annual data report form.
          $meeting['meeting_materials'] = \Drupal::service('foia_cfo.default')->linkOrFileFormatter($meeting_node->field_meeting_materials);
        }

        // Add link to the Agenda if there is one, as part of meeting materials.
        // Add as the first link - relative path.
        if (!empty($meeting_node->get('field_meeting_agenda')->getValue())) {
          if (!empty($meeting_node->get('field_meeting_date')->getValue()[0]['value'])) {
            $meeting_date = $meeting_node->get('field_meeting_date')->getValue()[0]['value'];
            $date_slug = date('F-j-Y', strtotime($meeting_date));
            $meeting_materials_agenda = [
              'item_title' => 'Agenda',
              'item_link' => '/chief-foia-officers-council/meeting/' . $date_slug,
            ];
            if (!empty($meeting['meeting_materials'])) {
              array_unshift($meeting['meeting_materials'], $meeting_materials_agenda);
            }
            else {
              $meeting['meeting_materials'][0] = $meeting_materials_agenda;
            }
          }
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
      ->setCacheTags($cache_tags)
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

    // Wrap Query in render context.
    $context = new RenderContext();
    $committee_nids = \Drupal::service('renderer')->executeInRenderContext($context, function () {
      $committee_query = \Drupal::entityQuery('node')
        ->condition('type', 'cfo_committee')
        ->condition('status', 1)
        ->sort('title');
      return $committee_query->execute();
    });

    if (!empty($committee_nids)) {

      // Loop through all committees.
      foreach ($committee_nids as $committee_nid) {

        // Load the committee node.
        if ($committee_node = $this->nodeStorage->load($committee_nid)) {
          $committee = [
            'committee_nid' => $committee_nid,
            'committee_title' => $committee_node->label(),
            'committee_updated' => $committee_node->changed->value,
            'committee_slug' => $committee_node->get('field_cfo_slug')->getValue()[0]['value'],
          ];
          $response[] = $committee;
        }

      }

    }

    // Set up the Cache Meta.
    $cacheMeta = (new CacheableMetadata())
      ->setCacheTags(['node_list:cfo_committee'])
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
   * Callback for `api/cfo/committee/{slug}` API method.
   *
   * Returns JSON Response full details for a committee based on node id passed.
   *
   * @param string $slug
   *   Node object of the committee passed as argument through routing.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Returns json object or false if the node did not load.
   */
  public function getCommittee(string $slug): CacheableJsonResponse {

    // Use this function to get the meeting node from the date string.
    $committee = \Drupal::service('foia_cfo.default')->contentFromSlug($slug, 'cfo_committee');

    if (
      !empty($committee)
      && $committee->isPublished()
      && $committee->bundle() === 'cfo_committee'
    ) {

      // Initialize the response with basic info.
      $response = [
        'committee_title' => $committee->label(),
        'committee_updated' => $committee->changed->value,
      ];

      // Add body HTML if any - use absolute links.
      if (
        $committee->hasField('body')
        && !empty($committee->get('body'))
        && !empty($committee->get('body')->getValue()[0]['value'])
      ) {
        $response['committee_body'] = \Drupal::service('foia_cfo.default')->absolutePathFormatter($committee->get('body')->getValue()[0]['value']);
      }

      // Attachments.
      if ( $committee->hasField('field_attachments') ) {
        $attachments =  $committee->get('field_attachments');
        $response['committee_attachments'] = \Drupal::service('foia_cfo.default')->buildAttachmentList($attachments);
      }

      // Add working groups.
      if ($committee->field_working_groups->count()) {
        $response['working_groups'] = \Drupal::service('foia_cfo.default')->workingGroupFieldFormatter($committee->field_working_groups);
      }

      // Set up the Cache Meta.
      $cacheMeta = (new CacheableMetadata())
        ->setCacheTags(['node:' . $committee->id()])
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
      return new CacheableJsonResponse([]);

    }

  }

}
