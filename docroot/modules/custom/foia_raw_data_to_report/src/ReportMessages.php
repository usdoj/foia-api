<?php

namespace Drupal\foia_raw_data_to_report;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\node\NodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Keeps large processing messages out of report node rendering.
 */
final class ReportMessages {

  public const INLINE_LINE_LIMIT = 1000;
  public const FILE_NOTICE = 'More than 1,000 lines of messages, which is too many to display.  See the messages text file on this node.';

  /**
   * Constructs the message storage service.
   */
  public function __construct(protected FileSystemInterface $fileSystem, protected FileRepositoryInterface $fileRepository, protected LoggerInterface $logger) {}

  /**
   * Saves the complete latest result inline or as a private text attachment.
   */
  public function save(NodeInterface $node, string $messages): void {
    $previous_file = $node->get('field_messages_file')->entity;
    $node->set('field_messages_file', []);
    // Normalize all newline styles so blank and trailing lines count equally.
    $normalized = str_replace(["\r\n", "\r"], "\n", $messages);
    $inline = $messages;
    if (substr_count($normalized, "\n") + 1 > self::INLINE_LINE_LIMIT) {
      try {
        $directory = 'private://request_data_tool/messages/' . $node->id();
        if (!$this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
          throw new \RuntimeException('Unable to prepare the messages directory.');
        }
        $file = $this->fileRepository->writeData($messages, $directory . '/' . $node->id() . '_messages.txt', FileExists::Replace);
        $file->setOwnerId($node->getOwnerId());
        $file->save();
        $node->set('field_messages_file', ['target_id' => $file->id()]);
        $inline = self::FILE_NOTICE;
      }
      catch (\Throwable $exception) {
        $inline = 'More than 1,000 lines of messages, which is too many to display. The messages text file could not be saved. Please contact an administrator.';
        $this->logger->error('Unable to save messages for report @nid: @error', [
          '@nid' => $node->id(),
          '@error' => $exception->getMessage(),
        ]);
      }
    }
    $node->set('field_messages', ['value' => $inline, 'format' => 'plain_text']);
    // Save before deleting any detached file, as with the XML attachment.
    $node->save();
    if ($previous_file && (string) $previous_file->id() !== (string) $node->get('field_messages_file')->target_id) {
      $previous_file->delete();
    }
  }

}
