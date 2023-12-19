<?php

namespace Drupal\foia_cfo\Controller;

/**
 * @file
 * Contains \Drupal\foia_cfo\Controller\CFOMeetingsController.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RenderContext;
use Drupal\time_field\Time;

/**
 * Controller routines for foia_cfo routes.
 */
class CFOMeetingsController extends ControllerBase {

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

    // Wrap Query in render context.
    $context = new RenderContext();
    $meeting_nids = \Drupal::service('renderer')->executeInRenderContext($context, function () {
      $meeting_query = \Drupal::entityQuery('node')
        ->accessCheck(TRUE)
        ->condition('type', 'cfo_meeting')
        ->condition('status', 1)
        ->sort('created');
      return $meeting_query->execute();
    });

    if (!empty($meeting_nids)) {

      // Loop through all meetings.
      foreach ($meeting_nids as $meeting_nid) {

        // Load the meeting node.
        if ($meeting_node = $this->nodeStorage->load($meeting_nid)) {

          // Prepare the meeting date for the url "slug".
          $meeting_date = $meeting_node->get('field_meeting_date')->getValue()[0]['value'];
          $meeting_slug = date('F-j-Y', strtotime($meeting_date));

          // Contents of this meeting.
          $meeting = [
            'meeting_nid' => $meeting_nid,
            'meeting_title' => $meeting_node->label(),
            'meeting_updated' => $meeting_node->changed->value,
            'meeting_slug' => $meeting_slug,
          ];

          // Add this meeting into the response.
          $response[] = $meeting;

        }

      }

    }

    // Set up the Cache Meta.
    $cacheMeta = (new CacheableMetadata())
      ->setCacheTags(['node_list:cfo_meeting'])
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
   * Callback for `api/cfo/meeting/{meeting_date_string}` API method.
   *
   * Returns JSON Response full details for a meeting based on date string.
   *
   * @param string $meeting_date_string
   *   Meeting date as string in the format M-d-Y.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Returns json object or false if the node did not load.
   */
  public function getMeeting(string $meeting_date_string): CacheableJsonResponse {

    // Use this function to get the meeting node from the date string.
    $meeting = \Drupal::service('foia_cfo.default')->meetingFromDateString($meeting_date_string);

    if (
      !empty($meeting)
      && $meeting->isPublished()
      && $meeting->bundle() === 'cfo_meeting'
    ) {

      // Initialize the response with basic info.
      $response = [
        'meeting_title' => $meeting->label(),
        'meeting_updated' => $meeting->changed->value,
      ];

      // Add Timestamp from meeting date.
      if (
        $meeting->hasField('field_meeting_date')
        && !empty($meeting->get('field_meeting_date'))
        && !empty($meeting->get('field_meeting_date')->getValue()[0]['value'])
      ) {
        $response['meeting_timestamp'] = strtotime($meeting->get('field_meeting_date')->getValue()[0]['value']);
      }

      // Add body HTML if any - use absolute links.
      if (
        $meeting->hasField('body')
        && !empty($meeting->get('body'))
        && !empty($meeting->get('body')->getValue()[0]['value'])
      ) {
        $response['meeting_body'] = \Drupal::service('foia_cfo.default')->absolutePathFormatter($meeting->get('body')->getValue()[0]['value']);
      }

      // Heading.
      if (
        $meeting->hasField('field_meeting_heading')
        && !empty($meeting->get('field_meeting_heading'))
        && !empty($meeting->get('field_meeting_heading')->getValue()[0]['value'])
      ) {
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
        ->setCacheTags(['node:' . $meeting->id()])
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
      return new CacheableJsonResponse([]);

    }

  }

}
