<?php

namespace Drupal\foia_upload_xml\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\foia_upload_xml\ReportUploadValidator;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AgencyXmlUploadForm.
 *
 * Provide a form to upload agency annual reports in NIEM-XML format.
 */
class AgencyXmlUploadForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Validator service to confirm report file can be processed.
   *
   * @var \Drupal\foia_upload_xml\ExistingReportCanBeOverwrittenValidator
   */
  protected $reportValidator;

  /**
   * AgencyXmlUploadForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\foia_upload_xml\ReportUploadValidator $report_upload_validator
   *   An object that can validate an uploaded report.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ReportUploadValidator $report_upload_validator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->reportValidator = $report_upload_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('foia_upload_xml.report_upload_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'foia_agency_xml_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['agency_report_xml'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Annual report'),
      '#description' => $this->t("Upload your agency's annual report in the standard NIEM-XML format. Use the file extension '.xml'."),
      '#upload_location' => 'temporary://foia-xml',
      '#upload_validators' => [
        'file_validate_extensions' => ['xml'],
      ],
      '#required' => TRUE,
    ];

    $form['next_step'] = [
      '#markup' => $this->t('Once you upload the NIEM-XML file, the data will be imported. This may take a few minutes.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Don't set or check the lock if they are uploading the file, just if the
    // form is actually being submitted.
    $element = $form_state->getTriggeringElement();
    if ($element['#id'] == 'edit-submit') {
      // Don't allow processing a report upload if the reporting agency has
      // an existing report in the current calendar year whose workflow
      // state indicates that it has been submitted or cleared.
      $file = $this->getUploadedFile($form_state);
      if ($file) {
        $this->reportValidator->validate($file, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Attempt to get a lock, tell them to try again if we can't.
    $lock = \Drupal::service('lock.persistent');

    // This is released in foia_upload_xml_execute_migration_finished().
    if ($lock->acquire('foia_upload_xml', 3600)) {
      $this->process($form_state);
    }
    else {
      $this->queue($form_state);
    }
  }

  /**
   * Add a report upload file to be processed by the queue.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function queue(FormStateInterface $form_state) {
    $user = User::load(\Drupal::currentUser()->id());
    $file = $this->getUploadedFile($form_state);
    $file = $this->prepareFile($file, 'public');
    $directory = $this->prepareDirectory('public');

    // Create a unique filename for this report based on the agency, year,
    // and, if the agency can't be found, the uploading user id.
    $report_data = \Drupal::service('foia_upload_xml.report_parser')->parse($file);
    $id = $report_data['agency_tid'] ?? 'user_' . $user->id();
    $year = $report_data['report_year'] ?? date('Y');
    $xml_upload_filename = "$directory/report_" . $year . "_" . $id . ".xml";
    $file = \FileRepositoryInterface::move($file, $xml_upload_filename, FileSystemInterface::EXISTS_RENAME);
    $item = new \stdClass();
    $item->fid = $file->id();
    $item->uid = $user->id();
    $item->agency = $report_data['agency_tid'] ?? $user->get('field_agency')->target_id;
    $item->report_year = $report_data['report_year'] ?? date('Y');

    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = \Drupal::service('queue')->get('foia_xml_report_import_worker');
    $queue->createItem($item);

    \Drupal::messenger()->addStatus($this->t('Your report has been queued. It will appear in your home page once it has successfully uploaded. You may navigate away from this page and check back shortly.'));
    $form_state->setRedirect('user.page');
  }

  /**
   * Process an uploaded report file immediately via the batch api.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function process(FormStateInterface $form_state) {
    $user = User::load(\Drupal::currentUser()->id());
    $file = $this->getUploadedFile($form_state);
    $file = $this->prepareFile($file, 'temporary');
    $directory = $this->prepareDirectory('temporary');

    // Get the user's agency abbreviation to put in the file name, so that
    // simultaneous uploads don't wipe out each other's files.
    // This is fine when the file is being processed immediately.
    $user_agency_nid = $user->get('field_agency')->target_id;
    $xml_upload_filename = "$directory/report_" . date('Y') . "_" . $user_agency_nid . ".xml";
    $file = file_move($file, $xml_upload_filename, FileSystemInterface::EXISTS_REPLACE);

    $batch = [
      'title' => $this->t('Importing Annual Report XML Data...'),
      'operations' => $this->getBatchOperations($file),
      'init_message' => $this->t('Commencing import'),
      'progress_message' => $this->t('Imported @current out of @total'),
      'error_message' => $this->t('An error occurred during import'),
      'finished' => 'foia_upload_xml_execute_migration_finished',
      'file' => drupal_get_path('module', 'foia_upload_xml') . '/foia_upload_xml.batch.inc',
    ];

    batch_set($batch);
  }

  /**
   * Operations for batch process.
   *
   * @param \Drupal\file\FileInterface $sourceFile
   *   The data source to be processed.
   *
   * @return array
   *   Array of operations to execute via batch.
   */
  protected function getBatchOperations(FileInterface $sourceFile) {
    /** @var \Drupal\foia_upload_xml\FoiaUploadXmlMigrationsProcessor */
    $processor = \Drupal::service('foia_upload_xml.migrations_processor');
    $operations = [];
    foreach ($processor->getMigrationsList() as $migration_list_item) {
      $operations[] = ['foia_upload_xml_execute_migration',
        [$migration_list_item, $sourceFile],
      ];
    }

    return $operations;
  }

  /**
   * Load the file uploaded in the agency_report_xml field.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The uploaded file object or NULL if one does not exist.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getUploadedFile(FormStateInterface $form_state) {
    $fid = $form_state->getValue(['agency_report_xml', 0]);
    if (empty($fid)) {
      return NULL;
    }

    $file_storage = $this->entityTypeManager->getStorage('file');
    return $file_storage->load($fid);
  }

  /**
   * Save and rename the uploaded file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The uploaded file.
   * @param string $file_scheme
   *   The file scheme to use when moving the file.  Either temporary,
   *   public, or private.
   *
   * @return \Drupal\file\FileInterface
   *   The uploaded file after it has been moved.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function prepareFile(FileInterface $file, string $file_scheme = 'temporary') {
    if (!$file_scheme == 'temporary') {
      // Store the file permanently so that it still exists when the queue goes
      // to process it.
      $file->setPermanent();
    }

    $file->save();

    return $file;
  }

  /**
   * Create a directory where reports should be saved, if it does not exist.
   *
   * @param string $file_scheme
   *   The file scheme indicating where to create the foia-xml directory,
   *   either temporary, public, or private.
   *
   * @return string
   *   The directory file path where the upload will be saved, including scheme.
   */
  protected function prepareDirectory(string $file_scheme = 'temporary') {
    $directory = $file_scheme . '://foia-xml';
    \Drupal::service('file_system')
      ->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

    return $directory;
  }

}
