<?php

namespace Drupal\foia_upload_xml\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\foia_upload_xml\FoiaXmlUploadBatchImport;

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
      '#markup' => $this->t('Once you upload the NIEM-XML file, you will be redirected to the @migrate page, where you will be able to complete the import.', [
        '@migrate' => Link::createFromRoute($this->t('Migrations'), 'entity.migration.list', ['migration_group' => 'foia_xml'])->toString(),
      ]),
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
    $file_storage = $this->entityTypeManager->getStorage('file');
    if (!empty($fid)) {
      $file = $file_storage->load($fid);
      // If we do not use temporary, then we should add $file->setPermanent().
      $file->save();
      $directory = 'temporary://foia-xml';
      file_prepare_directory($directory);
      file_move($file, "$directory/report.xml", FILE_EXISTS_REPLACE);
      $batch_import = new FoiaXmlUploadBatchImport();
      $migrations_list = $batch_import->getMigrationsList();
      $batch_import->execMigrations($migrations_list);
    }
  }

}
