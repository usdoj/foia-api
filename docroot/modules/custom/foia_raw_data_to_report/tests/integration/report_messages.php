<?php

/**
 * @file
 * Local checks: ddev drush php:script <path to this file>.
 */

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Site\Settings;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileExists;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\foia_raw_data_to_report\ReportMessages;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\system\FileDownloadController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

if (getenv('IS_DDEV_PROJECT') !== 'true') {
  throw new RuntimeException('Run this fixture-based integration check in DDEV only.');
}

$check = static function ($condition, string $message): void {
  if (!$condition) {
    throw new RuntimeException($message);
  }
};
$entities = [];
$report = NULL;
try {
  $agency = Term::create(['vid' => 'agency', 'name' => 'Messages check ' . bin2hex(random_bytes(6))]);
  $agency->save();
  $entities[] = $agency;
  $manager = User::create([
    'name' => 'messages-check-' . bin2hex(random_bytes(6)),
    'status' => 1,
    'roles' => ['agency_manager'],
    'field_agency' => $agency->id(),
  ]);
  $manager->save();
  $entities[] = $manager;
  $report = Node::create([
    'type' => 'raw_data_to_report',
    'title' => 'Messages storage check',
    'uid' => $manager->id(),
    'field_agency' => $agency->id(),
    'status' => 1,
  ]);
  $report->save();
  $messages = \Drupal::service('foia_raw_data_to_report.messages');
  foreach (["\n", "\r\n", "\r"] as $newline) {
    // The final blank line is the thousandth physical line.
    $text = str_repeat('Message' . $newline, 999);
    $messages->save($report, $text);
    $check($report->get('field_messages')->value === $text, 'Exactly 1,000 lines must stay inline.');
    $check($report->get('field_messages_file')->isEmpty(), 'Exactly 1,000 lines created an attachment.');
    $large = $text . $newline;
    $messages->save($report, $large);
    $file = $report->get('field_messages_file')->entity;
    $check($file !== NULL, '1,001 lines did not create an attachment.');
    $check($report->get('field_messages')->value === ReportMessages::FILE_NOTICE, 'Incorrect overflow notice.');
    $check($file->getFilename() === $report->id() . '_messages.txt', 'Incorrect attachment filename.');
    $check(str_starts_with($file->getFileUri(), 'private://'), 'Messages must be private.');
    $check(file_get_contents($file->getFileUri()) === $large, 'Attachment did not preserve complete text.');
    $check($file->isPermanent(), 'Attachment is not permanent.');
    $check((string) $file->getOwnerId() === (string) $manager->id(), 'Incorrect attachment owner.');
    $check($file->access('download', $manager), 'Agency manager cannot download messages.');
    $check(!$file->access('download', new AnonymousUserSession()), 'Anonymous users can download messages.');
    $check($report->get('field_messages_file')->access('view', $manager), 'Manager cannot view the file field.');
    $id = $file->id();
    $uri = $file->getFileUri();
    $replacement = $large . 'Latest messages';
    $messages->save($report, $replacement);
    $current = $report->get('field_messages_file')->entity;
    $check($current->getFilename() === $report->id() . '_messages.txt', 'Replacement changed the filename.');
    $check(file_get_contents($current->getFileUri()) === $replacement, 'Replacement retained stale messages.');
    $messages->save($report, 'Short result');
    $check($report->get('field_messages_file')->isEmpty(), 'Short result retained an attachment.');
    $check(!File::load($id), 'Detached managed file was not deleted.');
    $check(!file_exists($uri), 'Detached text file was not deleted.');
  }

  // A failed private filesystem must never cause oversized inline messages.
  $broken_filesystem = new class(\Drupal::service('stream_wrapper_manager'), Settings::getInstance()) extends FileSystem {

    /**
     * Simulates an unavailable private messages directory.
     */
    public function prepareDirectory(&$directory, $options = FileSystemInterface::MODIFY_PERMISSIONS) {
      return FALSE;
    }

  };
  $failed = new ReportMessages($broken_filesystem, \Drupal::service('file.repository'), \Drupal::logger('foia_raw_data_to_report'));
  $failed->save($report, str_repeat("Error\n", 1000));
  $check(str_contains($report->get('field_messages')->value, 'could not be saved'), 'Storage failure did not produce a short notice.');
  $check($report->get('field_messages_file')->isEmpty(), 'Storage failure retained an attachment.');
  $check(strlen($report->get('field_messages')->value) < 1000, 'Storage failure saved oversized inline messages.');

  // Starting the queue clears an old attachment even when input is missing.
  $messages->save($report, str_repeat("Error\n", 1000));
  $id = $report->get('field_messages_file')->target_id;
  \Drupal::service('plugin.manager.queue_worker')->createInstance('raw_data_to_report_processing')->processItem(['nid' => (int) $report->id()]);
  $report = Node::load($report->id());
  $check($report->get('field_messages_file')->isEmpty(), 'A new queue run retained the previous attachment.');
  $check(!File::load($id), 'A new queue run retained the previous managed file.');
  $check(str_contains($report->get('field_messages')->value, 'at least one'), 'Queue validation messages were not saved.');
  // Exercise a complete queue run with many malformed CSV records.
  $component = Node::create([
    'type' => 'agency_component',
    'title' => 'Messages component',
    'field_agency' => $agency->id(),
    'status' => 1,
  ]);
  $component->save();
  $entities[] = $component;
  $csv = implode(',', array_fill(0, 29, 'header')) . "\n" . str_repeat("bad,row\n", 1001);
  $source = \Drupal::service('file.repository')->writeData($csv, 'private://messages-check-' . $report->id() . '.csv', FileExists::Replace);
  $source->setPermanent();
  $source->save();
  $entities[] = $source;
  $upload = Paragraph::create([
    'type' => 'raw_data_component_upload',
    'field_agency_component' => $component->id(),
    'field_request_data_csv' => $source->id(),
  ]);
  $upload->save();
  $report->set('field_component_uploads', [$upload]);
  $report->set('field_foia_annual_report_yr', 2026);
  $report->save();
  $worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('raw_data_to_report_processing');
  $worker->processItem(['nid' => (int) $report->id()]);
  $report = Node::load($report->id());
  $check($report->get('field_messages')->value === ReportMessages::FILE_NOTICE, 'Queue saved oversized validation errors inline.');
  $file = $report->get('field_messages_file')->entity;
  $check($file !== NULL, 'Queue did not attach large validation results.');
  $text = file_get_contents($file->getFileUri());
  $check(str_contains($text, 'CSV record 1002 has 2 columns'), 'Queue attachment lost the last validation error.');
  $check(str_contains($text, 'No new XML report was generated.'), 'Queue attachment lost its result heading.');
  $check(str_contains($text, $source->getFilename()), 'Queue attachment lost its source filename.');
  $check($report->get('field_request_data_xml')->isEmpty(), 'Invalid CSV unexpectedly generated XML.');
  $controller = FileDownloadController::create(\Drupal::getContainer());
  $switcher = \Drupal::service('account_switcher');
  foreach ([new AnonymousUserSession(), $manager] as $account) {
    $switcher->switchTo($account);
    try {
      try {
        $response = $controller->download(new Request(['file' => substr($file->getFileUri(), 10)]));
        $check(!$account->isAnonymous(), 'Anonymous download of messages was allowed.');
        $check(file_get_contents($response->getFile()->getPathname()) === $text, 'Download lost message contents.');
      }
      catch (AccessDeniedHttpException $e) {
        $check($account->isAnonymous(), 'Manager download of messages was denied.');
      }
      if (!$account->isAnonymous()) {
        $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($report);
        $html = (string) \Drupal::service('renderer')->renderInIsolation($build);
        $check(str_contains($html, $file->getFilename()), 'Node did not display the messages download link.');
        $check(str_contains($html, 'More than 1,000 lines of messages'), 'Node did not display the overflow notice.');
        $check(!str_contains($html, 'CSV record 1002'), 'Node rendered the oversized messages.');
      }
    }
    finally {
      $switcher->switchBack();
    }
  }

  print "Report messages integration checks passed.\n";
}
finally {
  if ($report) {
    $file = $report->get('field_messages_file')->entity;
    $report->delete();
    $file?->delete();
  }
  foreach (array_reverse($entities) as $entity) {
    $entity->delete();
  }
}
