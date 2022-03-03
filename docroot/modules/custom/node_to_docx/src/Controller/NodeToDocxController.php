<?php

namespace Drupal\node_to_docx\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

/**
 * Class NodeToDocxController controller to generate docx files from nodes.
 *
 * @see /node/{node}/convert-node-to-docx
 */
class NodeToDocxController extends ControllerBase {

  /**
   * Generates a docx file from the content of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be processed.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   docx file response
   */
  public function convertNodeToDocx(NodeInterface $node) {
    // Get node to docx handler to generate docx.
    $handler = \Drupal::service('node_to_docx.handler');
    return $handler->convertToDocx($node);
  }

}
