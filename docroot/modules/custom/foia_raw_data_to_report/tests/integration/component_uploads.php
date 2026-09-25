<?php

/**
 * @file
 * Local integration checks: ddev drush php:script <path to this file>.
 */

use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\foia_raw_data_to_report\UploadAssignments;
use Drupal\foia_raw_data_to_report\SectionDataCsvValidator;
use Drupal\foia_raw_data_to_report\RequestStatisticsAggregator;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\system\FileDownloadController;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
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
$section_files = [];
$report = NULL;
$notification_store = NULL;
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
  foreach ($paragraphs as $delta => $paragraph) {
    $section_fees = $delta === 0 ? '908259' : '1816518';
    $section_exclusions = $delta === 0 ? '7' : '0';
    $section_foia_posts = $delta === 0 ? '145' : '0';
    $section_program_posts = $delta === 0 ? '823' : '17';
    $section_file = \Drupal::service('file.repository')->writeData(implode(',', SectionDataCsvValidator::HEADERS) . "\n39,1.85,7164103,1918485,$section_fees,$section_exclusions,$section_foia_posts,$section_program_posts", "private://section-check-$suffix-$delta.csv");
    $section_file->setOwnerId($manager->id())->save();
    $section_files[] = $section_file;
    $paragraph->set('section_ix_xi_data', $section_file->id());
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
  $notification_store = \Drupal::service('keyvalue.expirable')->get('foia_report_notifications.' . $manager->id());
  $process = static function () use ($worker, $report, $node_storage, $manager, $suffix) {
    $worker->processItem([
      'nid' => (int) $report->id(),
      'requester_uid' => (int) $manager->id(),
      'notification_id' => $suffix,
    ]);
    return $node_storage->loadUnchanged($report->id());
  };
  // Multiple failures in one upload must all reach the persisted messages.
  file_put_contents($files[1]->getFileUri(), "\nshort,row", FILE_APPEND);
  $report = $process();
  $message = $report->get('field_messages')->value;
  $check(str_contains($message, 'CSV validated.') && str_contains($message, '28 columns'), 'All files must be checked, with per-file results.');
  $check(str_contains($message, 'CSV record 1 has 28 columns') && str_contains($message, 'CSV record 2 has 2 columns'), 'Messages lost errors from later records.');
  foreach ($files as $file) {
    $check(str_contains($message, $file->getFilename()), 'Messages must identify each filename.');
  }
  $check($report->get('field_request_data_xml')->isEmpty(), 'Invalid input generated XML.');

  // The additional upload is required independently of its future contents.
  $first_upload = $report->get('field_component_uploads')->get(0)->entity;
  $section_id = $first_upload->get('section_ix_xi_data')->target_id;
  $first_upload->set('section_ix_xi_data', []);
  $check(count($first_upload->get('section_ix_xi_data')->validate()) > 0, 'Section IX-XI field was not required.');
  $check(isset(UploadAssignments::validate($report)[0]), 'Missing Section IX-XI upload passed assignment validation.');
  $first_upload->set('section_ix_xi_data', $section_id);

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
  $valid_section = file_get_contents($section_files[1]->getFileUri());
  file_put_contents($section_files[1]->getFileUri(), implode(',', SectionDataCsvValidator::HEADERS) . "\ninvalid,1.85,0,0,0,0,0,0");
  $report = $process();
  $check($report->get('field_request_data_xml')->isEmpty(), 'Invalid Section IX-XI data generated XML.');
  $section_messages = $report->get('field_messages')->value;
  $check(str_contains($section_messages, $section_files[1]->getFilename()) && str_contains($section_messages, 'Column A: Full-Time Employees must be an integer'), 'Section IX-XI error lacks file/column context.');
  file_put_contents($section_files[1]->getFileUri(), $valid_section);
  $report = $process();
  $xml = $report->get('field_request_data_xml')->entity;
  $check($xml && str_starts_with($xml->getFileUri(), 'private://'), 'Valid uploads did not produce private XML.');
  $files[] = $xml;
  $document = new DOMDocument();
  $check($document->loadXML(file_get_contents($xml->getFileUri())), 'Generated XML could not be parsed.');
  $xpath = new DOMXPath($document);
  $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
  $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
  $expected_totals = [
    1 => ['39', '1.85', '40.85', '7164103', '1918485', '9082588'],
    0 => ['78', '3.7', '81.7', '14328206', '3836970', '18165176'],
  ];
  foreach ($expected_totals as $id => $expected) {
    $fields = [
      'FullTimeEmployeeQuantity',
      'EquivalentFullTimeEmployeeQuantity',
      'TotalFullTimeStaffQuantity',
      'ProcessingCostAmount',
      'LitigationCostAmount',
      'TotalCostAmount',
    ];
    foreach ($fields as $index => $field) {
      $actual = $xpath->evaluate('string(//foia:PersonnelAndCost[@s:id="PC' . $id . '"]/foia:' . $field . ')');
      $check($actual === $expected[$index], 'Incorrect personnel/cost XML value for ' . $field);
    }
  }

  foreach ([1 => '7', 2 => '0', 0 => '7'] as $id => $expected) {
    $actual = $xpath->evaluate('string(//foia:SubsectionUsed[@s:id="SU' . $id . '"]/foia:TimesUsedQuantity)');
    $check($actual === $expected, 'Incorrect Section IX-XI subsection-use count.');
  }
  foreach ([1 => ['145', '823'], 2 => ['0', '17'], 0 => ['145', '840']] as $id => [$foia_posts, $program_posts]) {
    $base = '//foia:Subsection[@s:id="SP' . $id . '"]';
    $check($xpath->evaluate('string(' . $base . '/foia:PostedbyFOIAQuantity)') === $foia_posts, 'Incorrect FOIA posting count.');
    $check($xpath->evaluate('string(' . $base . '/foia:PostedbyProgramQuantity)') === $program_posts, 'Incorrect program posting count.');
  }
  $expected_fees = [
    1 => ['908259.0000', '0.1000'],
    2 => ['1816518.0000', '0.2000'],
    0 => ['2724777.0000', '0.1500'],
  ];
  foreach ($expected_fees as $id => [$amount, $ratio]) {
    $base = '//foia:FeesCollected[@s:id="FC' . $id . '"]';
    $check($xpath->evaluate('string(' . $base . '/foia:FeesCollectedAmount)') === $amount, 'Incorrect Section IX-XI fees.');
    $check($xpath->evaluate('string(' . $base . '/foia:FeesCollectedCostPercent)') === $ratio, 'Incorrect fee/cost ratio.');
  }
  $check(substr_count($report->get('field_messages')->value, 'CSV validated.') === 4, 'Successful retry did not replace earlier messages.');
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
  // An unknown appeal exemption exercises post-validation exception reporting.
  $row[24] = '01/03/2026';
  $row[25] = 'Affirmed on Appeal';
  $row[28] = 'invalid-code';
  $contents = implode(',', array_fill(0, 29, 'column')) . "\n" . implode(',', $row);
  file_put_contents($files[1]->getFileUri(), $contents);
  for ($attempt = 0; $attempt < 2; $attempt++) {
    try {
      $process();
      throw new LogicException('Expected the appeal-exemption exception.');
    }
    catch (RuntimeException $exception) {
      $check(str_contains($exception->getMessage(), 'Column AC'), 'Unexpected processing exception.');
      $check(str_starts_with($exception->getMessage(), 'Component ' . $components[1]->label() . ', CSV'), 'Processing exception must identify the component by name.');
      $report = $node_storage->loadUnchanged($report->id());
      $message = $report->get('field_messages')->value;
      $check(substr_count($message, 'CSV validated.') === 4, 'Exception lost validation messages.');
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
      foreach (array_merge($files, $section_files) as $file) {
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
  // All worker outcomes are durable and addressed to the requesting user.
  $notices = $notification_store->getAll();
  $check(count($notices) === 3, 'Expected success, validation, and processing failure notices without retry duplicates.');
  $messenger = \Drupal::messenger();
  $messenger->deleteAll();
  $notifications = \Drupal::service('foia_raw_data_to_report.notifications');
  $notifications->deliver();
  $check(!$messenger->all(), 'Notices leaked to a different account.');
  $switcher->switchTo($manager);
  try {
    $subscriber = \Drupal::service('foia_raw_data_to_report.notification_subscriber');
    $request = Request::create('/user');
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $event = new RequestEvent(\Drupal::service('http_kernel'), $request, HttpKernelInterface::MAIN_REQUEST);
    $subscriber->onRequest($event);
    $check(!$messenger->all(), 'AJAX consumed a notice.');
    $request->headers->remove('X-Requested-With');
    $request->setRequestFormat('json');
    $subscriber->onRequest($event);
    $check(!$messenger->all(), 'JSON consumed a notice.');
    $request->setRequestFormat('html');
    $subscriber->onRequest($event);
    $messages = $messenger->deleteAll();
    $check(count($messages['status'] ?? []) === 1 && count($messages['error'] ?? []) === 1, 'Missing completion notices or unread failure was not superseded.');
    $check(str_contains((string) $messages['status'][0], '<a href=') && str_contains((string) $messages['status'][0], $report->label()), 'Completion notice lacks the report link/title.');
    $notifications->deliver();
    $check(!$messenger->all(), 'Delivered notices appeared twice.');
    $notifications->record([
      'nid' => (int) $report->id(),
      'requester_uid' => (int) $manager->id(),
      'notification_id' => $suffix,
    ], 'failed');
    $notifications->deliver();
    $check(!$messenger->all(), 'Retry repeated a delivered failure notice.');
    $notifications->record(['nid' => (int) $report->id()], 'success');
    $check(count($notification_store->getAll()) === 3, 'Legacy queue metadata created a notice.');
    $notifications->record([
      'nid' => 0,
      'requester_uid' => (int) $manager->id(),
      'notification_id' => $suffix . '-missing',
    ], 'success');
    $notifications->deliver();
    $check(!$messenger->all(), 'Missing report produced a broken notification.');
  }
  finally {
    $switcher->switchBack();
  }
  print "PASS: multi-file validation, retry, empty uploads, XML preservation, duplicate/agency constraints, component choices, private and detached downloads, requester notifications and one-time delivery.\n";
}
finally {
  $notification_store?->deleteAll();
  if ($report) {
    $report->delete();
  }
  foreach (array_reverse($entities) as $entity) {
    $entity->delete();
  }
  foreach (array_merge($files, $section_files) as $file) {
    if ($stored = File::load($file->id())) {
      $stored->delete();
    }
  }
  print "Temporary integration fixtures removed.\n";
}
