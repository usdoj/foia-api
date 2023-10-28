<?php

namespace Drupal\node_to_docx;

use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Phpdocx\Create\CreateDocx;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class NodeToDocxHandler for converting nodes to docx.
 */
class NodeToDocxHandler implements ContainerAwareInterface {
  use ContainerAwareTrait;

  /**
   * Generates a docx file adding the content of a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to be processed.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The docx file response.
   */
  public function convertToDocx(NodeInterface $node) {
    // Check if phpdocx library is available.
    if ($this->isPhpdocxLibraryAvailable() === TRUE) {
      $filename = $node->id() . '-' . str_replace('/', '-', $node->getTitle());
      $view = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, 'node_to_docx');
      // The following line should cause the templates in this module to be
      // used.
      if ($node->getType() == 'quarterly_foia_report_data') {
        $view['#theme'] = 'node_to_docx_quarterly';
      }
      else {
        $view['#theme'] = 'node_to_docx';
      }
      $drupalMarkup = \Drupal::service('renderer')->render($view);
      // Debugging hint (remove period at end when uncommenting):
      // file_put_contents('/var/www/foia/docroot/debug.html', $drupalMarkup);.
      $this->generateDocxFromHtml($drupalMarkup->__toString(), $filename);
      return new RedirectResponse(Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString());
    }
    else {
      \Drupal::messenger()->addWarning(t('Phpdocx library is not included. Please copy phpdocx to "[your_drupal_app]/libraries/" directory or "[your_drupal_app]/!default_module_path/" directory.', [
        '!default_module_path' => \Drupal::service('extension.list.module')->getPath('node_to_docx'),
      ]));
      return new RedirectResponse(Url::fromRoute('entity.node.canonical', ['node' => $node->id()])->toString());
    }
  }

  /**
   * Checks if phpdocx library is available.
   *
   * @return bool
   *   Returns true if phpdox library is available.
   */
  public function isPhpdocxLibraryAvailable() {
    // Check if CreateDocx class can be instantiated.
    if (class_exists('Phpdocx\\Create\\CreateDocx')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Generates a docx file from the html code.
   *
   * @param string $html
   *   The html code to be processed.
   * @param string $file_name_output
   *   The file name to the output.
   */
  private function generateDocxFromHtml($html, $file_name_output) {
    $docx = new CreateDocx();
    $docx->embedHTML($html, ['useHTMLExtended' => TRUE]);

    $file_path = \Drupal::service('file_system')->realpath(\Drupal::config('system.file')->get('default_scheme') . '://');
    $docx->modifyPageLayout('letter-landscape');
    $docx->createDocx($file_path . '/' . $file_name_output);
    $buffer = file_get_contents($file_path . '/' . $file_name_output . '.docx');
    header('Content-Description: File Transfer');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: public, must-revalidate, max-age=0');
    header('Pragma: public');
    header('Expires: Sat, 1 Jan 1970 01:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Content-Type: application/force-download');
    header('Content-Type: application/octet-stream', FALSE);
    header('Content-Type: application/download', FALSE);
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document', FALSE);
    if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) or empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
      header('Content-Length: ' . strlen($buffer));
    }
    header('Content-disposition: attachment; filename="' . $file_name_output . '.docx"');
    echo $buffer;

    exit;
  }

}
