<?php
/**
 * @file
 * Contains \Drupal\node_to_docx\Controller\NodeToDocxController.
 */
namespace Drupal\node_to_docx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

class NodeToDocxController extends ControllerBase {
  /**
   *  Generates a docx file from the content of a node
   *
   *  @param \Drupal\node\NodeInterface $node
   *   The node to be processed.
   *  @return docx file response
   */
  public function convertNodeToDocx(NodeInterface $node) {
    // get node to docx handler to generate docx
  	$handler = \Drupal::service('node_to_docx.handler');
    return $handler->convertToDocx($node);
  }
}