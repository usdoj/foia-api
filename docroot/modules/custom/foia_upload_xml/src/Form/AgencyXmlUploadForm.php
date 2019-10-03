<?php

namespace Drupal\foia_upload_xml\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\migrate_plus\Entity\Migration;

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
   * AgencyXmlUploadForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue(['agency_report_xml', 0]);
    if (empty($fid)) {
      return;
    }

    $file_storage = $this->entityTypeManager->getStorage('file');
    $file = $file_storage->load($fid);
    // If we do not use temporary, then we should add $file->setPermanent().
    $file->save();
    $directory = 'temporary://foia-xml';
    \Drupal::service('file_system')->prepareDirectory($directory, FILE_CREATE_DIRECTORY);

    // Get the user's agency abbreviation to put in the file name, so that
    // simulataneous uploads don't wipe out each other's files.
    $user = User::load(\Drupal::currentUser()->id());
    $user_agency_nid = $user->get('field_agency')->target_id;
    $xml_upload_filename =
      "$directory/report_" . date('Y') . "_" . $user_agency_nid . ".xml";
    file_move($file, $xml_upload_filename, FILE_EXISTS_REPLACE);

    // Load the migrations, set them to use this new filename, and save them.
    $migrations_list = $this->getMigrationsList();
    foreach ($migrations_list as $migration_list_item) {
      $migration = Migration::load($migration_list_item);
      $source = $migration->get('source');
      $source['urls'] = $xml_upload_filename;
      $migration->set('source', $source);
      $migration->save();
    }

    $batch = [
      'title' => $this->t('Importing Annual Report XML Data...'),
      'operations' => $this->getBatchOperations(),
      'init_message' => $this->t('Commencing import'),
      'progress_message' => $this->t('Imported @current out of @total'),
      'error_message' => $this->t('An error occurred during import'),
      'finished' => 'foia_upload_xml_execute_migration_finished',
      'file' => drupal_get_path('module', 'foia_upload_xml') . '/foia_upload_xml.batch.inc',
    ];

    batch_set($batch);
  }

  /**
   * Fetches an array of migrations to run to import the Annual Report XML.
   *
   * @return string[]
   *   List of migrations.
   */
  protected function getMigrationsList() {
    $migrations_list = [
      'component',
      'component_ix_personnel',
      'component_va_requests',
      'component_vb1_requests',
      'component_vb2_requests',
      'component_vb3_requests',
      'component_via_disposition',
      'component_vib_disposition',
      'component_vic1_applied_exemptions',
      'component_vic2_nonexemption_denial',
      'component_vic3_other_denial',
      'component_vic4_response_time',
      'component_vic5_oldest_pending',
      'component_viia_processed_requests',
      'component_viib_processed_requests',
      'component_viic1_simple_response',
      'component_viic2_complex_response',
      'component_viic3_expedited_response',
      'component_viid_pending_requests',
      'component_viie_oldest_pending',
      'component_viiia_expedited_processing',
      'component_viiib_fee_waiver',
      'component_xia_subsection_c',
      'component_xib_subsection_a2',
      'component_xiia',
      'component_xiib',
      'component_xiic',
      'component_xiid1',
      'component_xiid2',
      'component_xiie1',
      'component_xiie2',
      'component_x_fees',
      'foia_vb2_other',
      'foia_vic3_other',
      'foia_iv_details',
      'foia_iv_statute',
      'foia_va_requests',
      'foia_vb1_requests',
      'foia_vb2',
      'foia_vb3_requests',
      'foia_via_disposition',
      'foia_vib_disposition',
      'foia_vic1_applied_exemptions',
      'foia_vic2_nonexemption_denial',
      'foia_vic3',
      'foia_vic4_response_time',
      'foia_vic5_oldest_pending',
      'foia_viia_processed_requests',
      'foia_viib_processed_requests',
      'foia_viic1_simple_response',
      'foia_viic2_complex_response',
      'foia_viic3_expedited_response',
      'foia_viid_pending_requests',
      'foia_viie_oldest_pending',
      'foia_viiia_expedited_processing',
      'foia_viiib_fee_waiver',
      'foia_ix_personnel',
      'foia_x_fees',
      'foia_xia_subsection_c',
      'foia_xib_subsection_a2',
      'foia_xiia',
      'foia_xiib',
      'foia_xiic',
      'foia_xiid1',
      'foia_xiid2',
      'foia_xiie1',
      'foia_xiie2',
      'foia_agency_report',
    ];

    return $migrations_list;
  }

  /**
   * Operations for batch process.
   *
   * @return array
   *   Array of operations to execute via batch.
   */
  protected function getBatchOperations() {
    $migrations_list = $this->getMigrationsList();
    $operations = [];
    foreach ($migrations_list as $migration_list_item) {
      $operations[] = ['foia_upload_xml_execute_migration',
        [$migration_list_item],
      ];
    }

    return $operations;
  }

}
