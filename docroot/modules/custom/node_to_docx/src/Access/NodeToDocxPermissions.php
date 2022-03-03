<?php

namespace Drupal\node_to_docx\Access;

use Drupal\node\Entity\NodeType;

/**
 * Class NodeToDocxPermissions are permissions for node_to_docx.
 *
 * @see /admin/people/permissions/module/node_to_docx
 */
class NodeToDocxPermissions {

  /**
   * Return the permissions to generate docx.
   */
  public function permissions() {
    $permissions = [];
    // Permission of all node types.
    $permissions += [
      'generate docx using node to docx' => [
        'title' => t('Generate docx for <strong>all content types</strong>'),
      ],
    ];
    // Permissions of every node type.
    $types = NodeType::loadMultiple();
    foreach ($types as $key => $type) {
      $permissions += [
        'generate docx using node to docx: ' . $type->get('type') => [
          'title' => t('Generate docx for content type <strong>%type_name</strong>', ['%type_name' => $type->get('name')]),
        ],
      ];
    }

    return $permissions;
  }

}
