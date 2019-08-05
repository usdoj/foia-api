<?php
/**
 * @file
 * Contains \Drupal\node_to_docx\Access\NodeToDocxPermissions.
 */
namespace Drupal\node_to_docx\Access;

Use \Drupal\node\Entity\NodeType;

class NodeToDocxPermissions {
  /**
   *  Return the permissions to generate docx
   */
  public function permissions() {
    $permissions = array();
    // permission of all node types
    $permissions += array(
      'generate docx using node to docx' => array(
        'title' => t('Generate docx for <strong>all content types</strong>'),
      ),
    );
    // permissions of every node type
    $types = NodeType::loadMultiple();
    foreach ($types as $key => $type) {
      $permissions += array(
        'generate docx using node to docx: ' . $type->get('type') => array(
          'title' => t('Generate docx for content type <strong>%type_name</strong>', array('%type_name' => $type->get('name'))),
        ),
      );
    }

    return $permissions;
  }
}