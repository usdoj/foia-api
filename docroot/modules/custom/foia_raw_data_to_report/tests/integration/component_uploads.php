<?php

/**
 * @file
 * Local integration checks: ddev drush php:script <path to this file>.
 */

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\foia_raw_data_to_report\UploadAssignments;
use Drupal\foia_raw_data_to_report\RequestStatisticsAggregator;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\system\FileDownloadController;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
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
$agencies = [];
$components = [];
$paragraphs = [];
$files = [];
$report = NULL;
$suffix = bin2hex(random_bytes(6));
$node_storage = \Drupal::entityTypeManager()->getStorage('node');
$worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('raw_data_to_report_processing');
try {
  foreach (['One', 'Two'] as $label) {
    $agency = Term::create(['vid' => 'agency', 'name' => "CSV check $label $suffix"]);
    $agency->save();
    $entities[] = $agency;
    $agencies[] = $agency;
  }
  $manager = User::create([
    'name' => "csv-check-$suffix",
    'status' => 1,
    'roles' => ['agency_manager'],
    'field_agency' => $agencies[0]->id(),
  ]);
  $manager->save();
  $entities[] = $manager;
  foreach ([0, 0, 1] as $delta => $agency_index) {
    $component = Node::create([
      'type' => 'agency_component',
      'title' => "CSV component $delta $suffix",
      'status' => 1,
      'field_agency' => $agencies[$agency_index]->id(),
    ]);
    $component->save();
    $components[] = $component;
    $entities[] = $component;
  }
  foreach ([29, 28] as $delta => $columns) {
    $file = \Drupal::service('file.repository')->writeData(implode(',', array_fill(0, $columns, 'column')), "private://csv-check-$suffix-$delta.csv");
    $file->setOwnerId($manager->id())->save();
    $files[] = $file;
    $paragraph = Paragraph::create([
      'type' => 'raw_data_component_upload',
      'field_agency_component' => $components[$delta]->id(),
      'field_request_data_csv' => $file->id(),
    ]);
    $paragraphs[] = $paragraph;
  }
  $report = Node::create([
    'type' => 'raw_data_to_report',
    'title' => "CSV report $suffix",
    'status' => 1,
    'uid' => $manager->id(),
    'field_foia_annual_report_yr' => 2026,
    'field_agency' => $agencies[0]->id(),
    'field_component_uploads' => array_map(static fn($p) => ['entity' => $p], $paragraphs),
  ]);
  // Components must validate before the parent report has a node ID.
  foreach ($paragraphs as $paragraph) {
    $paragraph->setParentEntity($report, 'field_component_uploads');
    $check(count($paragraph->get('field_agency_component')->validate()) === 0, 'Component reference rejected on an unsaved report.');
  }
  $report->save();
  $process = static function () use ($worker, $report, $node_storage) {
    $worker->processItem(['nid' => (int) $report->id()]);
    return $node_storage->loadUnchanged($report->id());
  };
  $report = $process();
  $message = $report->get('field_messages')->value;
  $check(str_contains($message, 'CSV validated.') && str_contains($message, '28 columns'), 'All files must be checked, with per-file results.');
  foreach ($files as $file) {
    $check(str_contains($message, $file->getFilename()), 'Messages must identify each filename.');
  }
  $check($report->get('field_request_data_xml')->isEmpty(), 'Invalid input generated XML.');

  // Entity validation and queue processing must reject bad assignments.
  $second = $report->get('field_component_uploads')->get(1)->entity;
  $second->set('field_agency_component', $components[0]->id());
  $check(isset(UploadAssignments::validate($report)[1]), 'Duplicate component accepted.');
  $violations = $report->get('field_component_uploads')->validate();
  $check(count($violations) > 0, 'Upload field constraint did not reject a duplicate.');
  $second->set('field_agency_component', $components[2]->id());
  $check(isset(UploadAssignments::validate($report)[1]), 'Foreign agency component accepted.');
  $second->set('field_agency_component', $components[1]->id());

  $selection = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance([
    'target_type' => 'node',
    'handler' => 'raw_data_component',
    'agency_id' => $agencies[0]->id(),
  ]);
  $choices = $selection->getReferenceableEntities();
  $check(isset($choices['agency_component'][$components[0]->id()]) && !isset($choices['agency_component'][$components[2]->id()]), 'Component selector leaked another agency.');
  $selection = \Drupal::service('plugin.manager.entity_reference_selection')->getInstance([
    'target_type' => 'node',
    'handler' => 'raw_data_component',
    'agency_id' => 0,
  ]);
  $check(!$selection->getReferenceableEntities(), 'Unselected agency must have no component choices.');

  file_put_contents($files[1]->getFileUri(), implode(',', array_fill(0, 29, 'column')));
  $report = $process();
  $xml = $report->get('field_request_data_xml')->entity;
  $check($xml && str_starts_with($xml->getFileUri(), 'private://'), 'Valid uploads did not produce private XML.');
  $files[] = $xml;
  $check(substr_count($report->get('field_messages')->value, 'CSV validated.') === 2, 'Successful retry did not replace earlier messages.');
  // An aggregation exception must append to validation messages and retain XML.
  $row = array_fill(0, 29, '');
  $row[0] = 'Component';
  $row[1] = 'Exception fixture';
  $row[2] = 'N';
  $row[3] = '20';
  $row[23] = '01/02/2026';
  $contents = implode(',', array_fill(0, 29, 'column')) . "\n" . implode(',', $row);
  file_put_contents($files[1]->getFileUri(), $contents);
  // Appeal-only rows must not count as initial requests or pending requests.
  $counts = (new RequestStatisticsAggregator())->aggregate([
    ['component_id' => $components[1]->id(), 'uri' => $files[1]->getFileUri()],
  ], 2026);
  $check($counts['overall'] === ['pending_start' => 0, 'received' => 0, 'processed' => 0, 'pending_end' => 0], 'Appeal-only row contributed to request statistics.');
  // Invalid appeal dates still exercise post-validation exception reporting.
  $row[23] = 'invalid-date';
  $contents = implode(',', array_fill(0, 29, 'column')) . "\n" . implode(',', $row);
  file_put_contents($files[1]->getFileUri(), $contents);
  for ($attempt = 0; $attempt < 2; $attempt++) {
    try {
      $process();
      throw new LogicException('Expected the appeal-statistics date exception.');
    }
    catch (RuntimeException $exception) {
      $check(str_contains($exception->getMessage(), 'Column X'), 'Unexpected processing exception.');
      $report = $node_storage->loadUnchanged($report->id());
      $message = $report->get('field_messages')->value;
      $check(substr_count($message, 'CSV validated.') === 2, 'Exception lost validation messages.');
      $check(substr_count($message, 'XML report processing failed:') === 1, 'Retry duplicated exception messages.');
      $check(str_ends_with($message, $exception->getMessage()), 'Exception details were not appended.');
      $check($report->get('field_messages')->format === 'plain_text', 'Exception message must be plain text.');
      $check($report->get('field_request_data_xml')->target_id === $xml->id(), 'Exception replaced existing XML.');
    }
  }
  file_put_contents($files[1]->getFileUri(), 'bad,csv');
  $report = $process();
  $check($report->get('field_request_data_xml')->target_id === $xml->id(), 'Failed retry replaced existing XML.');

  // The widget must retain an owned temporary CSV before saving its paragraph.
  $directory = 'private://request_data_tool/components';
  \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
  $temporary = \Drupal::service('file.repository')->writeData('temporary', $directory . "/temporary-$suffix.csv");
  $temporary->setOwnerId($manager->id());
  $temporary->setTemporary();
  $temporary->save();
  $files[] = $temporary;
  $controller = FileDownloadController::create(\Drupal::getContainer());
  $switcher = \Drupal::service('account_switcher');
  foreach ([new AnonymousUserSession(), $manager] as $account) {
    $switcher->switchTo($account);
    try {
      foreach ($files as $file) {
        try {
          $response = $controller->download(new Request(['file' => substr($file->getFileUri(), 10)]));
          $check(!$account->isAnonymous() && $response->getStatusCode() === 200, 'Anonymous download allowed.');
        }
        catch (AccessDeniedHttpException $e) {
          $check($account->isAnonymous(), 'Manager download denied.');
        }
      }
    }
    finally {
      $switcher->switchBack();
    }
  }
  $report->set('field_component_uploads', []);
  $report->save();
  $report = $process();
  $check(str_contains($report->get('field_messages')->value, 'at least one'), 'Empty upload list was accepted.');
  $check($report->get('field_request_data_xml')->target_id === $xml->id(), 'Empty upload list replaced XML.');
  \Drupal::entityTypeManager()->getAccessControlHandler('file')->resetCache();
  $switcher->switchTo($manager);
  try {
    try {
      $controller->download(new Request(['file' => substr($files[0]->getFileUri(), 10)]));
      throw new RuntimeException('Detached paragraph file download was allowed.');
    }
    catch (AccessDeniedHttpException $e) {
      // A detached paragraph cannot grant access through its former report.
    }
  }
  finally {
    $switcher->switchBack();
  }
  print "PASS: multi-file validation, retry, empty uploads, XML preservation, duplicate/agency constraints, component choices, private and detached downloads.\n";
}
finally {
  if ($report) {
    $report->delete();
  }
  foreach (array_reverse($entities) as $entity) {
    $entity->delete();
  }
  foreach ($files as $file) {
    if ($stored = File::load($file->id())) {
      $stored->delete();
    }
  }
  print "Temporary integration fixtures removed.\n";
}
