<?php

namespace Drupal\foia_raw_data_to_report\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Downloads the current generated XML using the existing report access rules.
 */
final class XmlDownloadController extends ControllerBase {

  /**
   * Allows the tab only for accessible reports with an accessible XML file.
   */
  public function access(NodeInterface $node, AccountInterface $account): AccessResultInterface {
    $access = AccessResult::allowedIf($account->isAuthenticated() && $node->bundle() === 'raw_data_to_report')
      ->cachePerUser()
      ->addCacheableDependency($node);
    if (!$access->isAllowed()) {
      return $access;
    }
    $field = $node->get('field_request_data_xml');
    $file = $field->entity;
    if (!$file instanceof FileInterface) {
      return AccessResult::forbidden()->addCacheableDependency($access);
    }
    return $access
      ->andIf($node->access('view', $account, TRUE))
      ->andIf($field->access('view', $account, TRUE))
      ->andIf($file->access('view', $account, TRUE))
      ->andIf($file->access('download', $account, TRUE))
      ->addCacheableDependency($file);
  }

  /**
   * Serves the attachment without rebuilding XML or loading it into memory.
   */
  public function download(NodeInterface $node): BinaryFileResponse {
    if (!$this->access($node, $this->currentUser())->isAllowed()) {
      throw new AccessDeniedHttpException();
    }
    $file = $node->get('field_request_data_xml')->entity;
    if (!is_file($file->getFileUri()) || !is_readable($file->getFileUri())) {
      throw new NotFoundHttpException('The generated XML file is unavailable.');
    }
    $response = new BinaryFileResponse($file->getFileUri(), 200, [
      'Content-Type' => 'text/xml; charset=UTF-8',
      'Cache-Control' => 'private, no-store, max-age=0',
    ], FALSE);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFilename());
    return $response;
  }

}
