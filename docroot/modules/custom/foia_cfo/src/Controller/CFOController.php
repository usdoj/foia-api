<?php

/**
 * @file
 * Contains \Drupal\foia_cfo\Controller\TestAPIController.
 */

namespace Drupal\foia_cfo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\Entity\File;
use Drupal\views\Views;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller routines for foia_cfo routes.
 */
class CFOController extends ControllerBase {

  /**
   * Node Storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $node_storage;

//  /**
//   * @var \Drupal\Core\Entity\EntityStorageInterface
//   */
//  private $paragraph_storage;

  /**
   * Constructor for this class.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct() {
    $this->node_storage = \Drupal::entityTypeManager()->getStorage('node');
  }

  /**
   * Callback for `my-api/get.json` API method.
   */
  public function getCouncil(Request $request) {

    // Initialize the response.
    $response = [];

    // Load the view for the CFO API and execute the "council" display.
    $view = Views::getView('cfo_council_api');
    $view->setDisplay('council');
    $view->execute();

    // Should only be one result.
    foreach ($view->result as $resultRow) {

      // Title and body of the Council node.
      $response['title'] = $resultRow->_entity->label();
      if ($resultRow->_entity->get('body')) {
        $response['body'] = $resultRow->_entity->get('body')->getValue()[0]['value'];
      }

      /** @var \Drupal\Core\Field\EntityReferenceFieldItemList $committees */
      $committees = $resultRow->_entity->get('field_council_committees');

      if (!empty($committees)) {

        // Store committees as array elements.
        $response['committees'] = [];

        foreach ($committees as $committee) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $committee */
          $nid = $committee->getValue()['target_id'];
          $committee_node = $this->node_storage->load($nid);
          $response['committees'][] = [
            'committee_title' => $committee_node->label(),
            'committee_body' => $committee_node->body->view('default')[0]['#text'],
          ];
        }
      }

    }

    // Set the display for the meetings.
    $view_meetings = Views::getView('cfo_council_api');
    $view_meetings->setDisplay('meetings');
    $view_meetings->execute();

    // Store meetings as array elements.
    $response['meetings'] = [];

    foreach ($view_meetings->result as $resultRow) {

      // Initialize this meeting.
      $meeting = [];

      $meeting['meeting_title'] = $resultRow->_entity->label();
      if (!empty($resultRow->_entity->body->view('default')[0]['#text'])) {
        $meeting['meeting_body'] = $resultRow->_entity->body->view('default')[0]['#text'];
      }

      if (!empty($resultRow->_entity->get('field_meeting_agenda'))) {
        /** @var \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $agenda */
        $agenda = $resultRow->_entity->get('field_meeting_agenda');
      }

      if ($resultRow->_entity->field_meeting_materials->count()) {
        $meeting['meeting_materials'] = self::linkOrFileFormatter($resultRow->_entity->field_meeting_materials);
      }

      if ($resultRow->_entity->field_meeting_documents->count()) {
        $meeting['meeting_documents'] = self::linkOrFileFormatter($resultRow->_entity->field_meeting_documents);
      }

      $response['meetings'][] = $meeting;

    }

    // Return the response array converted to json response.
    return new JsonResponse($response);

  }

  /**
   * Formats "Link or File" paragraph types.
   *
   * @param \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $field
   *   The field.
   *
   * @return array
   *   Lables and links to either the url or the file.
   */
  private function linkOrFileFormatter(EntityReferenceRevisionsFieldItemList $field) {

    // Initialize return array.
    $return = [];

    foreach ($field->referencedEntities() as $item) {

      $return_item = [];
      $return_item['item_title'] = $item->get('field_link_label')->getValue()[0]['value'];
      if (!empty($item->get('field_link_link')->getValue()[0]['uri'])) {
        $return_item['item_link'] = $item->get('field_link_link')->getValue()[0]['uri'];
      }
      elseif (!empty($item->get('field_link_file')->getValue()[0]['target_id'])) {
        $fid = $item->get('field_link_file')->getValue()[0]['target_id'];
        $file = File::load($fid);
        $return_item['item_link'] = $file->createFileUrl();
      }

      $return[] = $return_item;

    }

    return $return;

  }

}
