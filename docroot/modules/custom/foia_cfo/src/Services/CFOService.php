<?php

namespace Drupal\foia_cfo\Services;

use Drupal\Core\Render\RenderContext;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Class CFOService various functions/services for CFO.
 */
class CFOService {

  /**
   * Constructs a new CFOService object.
   */
  public function __construct() {

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
  public function linkOrFileFormatter(EntityReferenceRevisionsFieldItemList $field): array {

    // Initialize return array.
    $return = [];

    // Loop over the referenced paragraph entities.
    foreach ($field->referencedEntities() as $item) {

      // Initialize this item.
      $return_item = [];

      // Set the item label.
      if (!empty($item->get('field_link_label')->getValue())) {
        $return_item['item_title'] = $item->get('field_link_label')
          ->getValue()[0]['value'];
      }

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
      if (!empty($return_item)) {
        $return[] = $return_item;
      }

    }

    // Returns array of items with labels and links.
    return $return;

  }

  /**
   * Formats "Working Group" paragraph type.
   *
   * @param \Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList $field
   *   The field.
   *
   * @return array
   *   Labels and links to either the url or the file.
   */
  public function workingGroupFieldFormatter(EntityReferenceRevisionsFieldItemList $field): array {

    // Initialize return array.
    $return = [];

    // Loop over the referenced paragraph entities.
    foreach ($field->referencedEntities() as $item) {

      // Initialize this item.
      $return_item = [];

      // Title.
      if (
        $item->hasField('field_title')
        && !empty($item->get('field_title')->getValue()[0]['value'])
      ) {
        $return_item['item_title'] = $item->get('field_title')->getValue()[0]['value'];
      }

      // Body.
      if (
        $item->hasField('field_body')
        && !empty($item->get('field_body')->getValue()[0]['value'])
      ) {
        $return_item['item_body'] = $item->get('field_body')->getValue()[0]['value'];
      }

      // File Attachments.
      if ($item->hasField('field_attachments')) {
        $attachments = $item->get('field_attachments');
        $list = $this->buildAttachmentList($attachments);
        if (!empty($list)) {
          $return_item['item_attachments'] = $list;
        }
      }

      // Add this item to the return array.
      if (!empty($return_item)) {
        $return[] = $return_item;
      }

    }

    // Returns array of items with labels and links.
    return $return;

  }

  /**
   * Adds the absolute path to src and href parameter values.
   *
   * @param string $input
   *   Input string (html).
   *
   * @return string
   *   Input string with absolute paths to src and href.
   */
  public function absolutePathFormatter(string $input): string {

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
   * Grab the meeting using the date "slug".
   *
   * @param string $meeting_date_string
   *   String used to query the meeting date field.
   *
   * @return \Drupal\node\Entity\Node|false
   *   Return the meeting node or false if no match.
   */
  public function meetingFromDateString(string $meeting_date_string) {

    // Convert the slug to a workable string.
    $meeting_date_string = str_replace('-', ' ', $meeting_date_string);

    // Convert the string to a date value, used in the query "like".
    $meeting_date_value = date('Y-m-d', strtotime($meeting_date_string)) . 'T%';

    // Wrap Query in render context.
    $context_meeting = new RenderContext();
    $meeting_query = \Drupal::service('renderer')->executeInRenderContext($context_meeting, function () use ($meeting_date_value) {
      // Query for the meeting - match just date (not time) hence "like".
      $meeting_query = \Drupal::entityQuery('node')
        ->condition('type', 'cfo_meeting')
        ->condition('status', 1)
        ->condition('field_meeting_date', $meeting_date_value, 'LIKE')
        ->sort('created')
        ->range(0, 1);
      return $meeting_query->execute();
    });

    // If we have a got match, return the meeting node otherwise FALSE.
    if (!empty(array_values($meeting_query)[0])) {
      $meeting_nid = array_values($meeting_query)[0];
      return Node::load($meeting_nid);
    }
    else {
      return FALSE;
    }

  }

  /**
   * Returns a node object that matches the slug and content type passed.
   *
   * @param string $slug
   *   Matches field_cfo_slug.
   * @param string $content_type
   *   Matches node content type.
   *
   * @return \Drupal\node\Entity\Node|false
   *   Node object or false.
   */
  public function contentFromSlug(string $slug, string $content_type) {

    // Wrap Query in render context.
    $render_context = new RenderContext();
    $render_query = \Drupal::service('renderer')->executeInRenderContext($render_context, function () use ($slug, $content_type) {
      // Query for the meeting - match just date (not time) hence "like".
      $query = \Drupal::entityQuery('node')
        ->condition('type', $content_type)
        ->condition('status', 1)
        ->condition('field_cfo_slug', $slug)
        ->sort('created')
        ->range(0, 1);
      return $query->execute();
    });

    // If we have a got match, return the node otherwise FALSE.
    if (!empty(array_values($render_query)[0])) {
      $nid = array_values($render_query)[0];
      return Node::load($nid);
    }
    else {
      return FALSE;
    }

  }

  /**
   * Returns array of attachment titles & urls.
   *
   * @var array $field
   *   Field.
   *
   * @return array
   *   Return the array.
   */
  public function buildAttachmentList(EntityReferenceRevisionsFieldItemList $field): array {
    $attachmentResultList = [];

    foreach ($field->referencedEntities() as $item) {
      $fileItems = [];

      // Title.
      if (
        $item->hasField('field_title')
        && !empty($item->get('field_title')->getValue()[0]['value'])
      ) {
        $fileItems['attachment_title'] = $item->get('field_title')->getValue()[0]['value'];
      }

      // File.
      if ($item->hasField('field_attachment')) {
        $attachment = $item->get('field_attachment')->getValue();
        $fid = $attachment[0]['target_id'];
        if (empty($fid)) {
          continue;
        }
        $file = File::load($fid);
        $fileItems['attachment_file'] = $file->createFileUrl(FALSE);
      }

      if (!empty($fileItems)) {
        $attachmentResultList[] = $fileItems;
      }
    }

    return $attachmentResultList;
  }

}
