<?php

namespace Drupal\foia_api\Plugin\rest\resource;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformElementManagerInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionForm;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents webform submissions as a resource.
 *
 * @RestResource(
 *   id = "webform_submission",
 *   label = @Translation("Webform submission"),
 *   uri_paths = {
 *     "https://www.drupal.org/link-relations/create" = "/api/webform/submit",
 *   },
 * )
 */
class WebformSubmissionResource extends ResourceBase {

  const INVALID_FORM_ID_ERROR = 'Invalid form ID. Check the agency metadata for the latest form information for the desired agency component.';

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * File usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * Constructs a new WebformSubmissionResource instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializerFormats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   Entity query service.
   * @param \Drupal\webform\WebformElementManagerInterface $elementManager
   *   Webform element manager.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\file\FileUsage\FileUsageInterface $fileUsage
   *   File usage service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializerFormats, LoggerInterface $logger, QueryFactory $queryFactory, WebformElementManagerInterface $elementManager, FileSystemInterface $fileSystem, FileUsageInterface $fileUsage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializerFormats, $logger);
    $this->queryFactory = $queryFactory;
    $this->elementManager = $elementManager;
    $this->fileSystem = $fileSystem;
    $this->fileUsage = $fileUsage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity.query'),
      $container->get('plugin.manager.webform.element'),
      $container->get('file_system'),
      $container->get('file.usage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function post(array $data) {
    $webformId = isset($data['id']) ? $data['id'] : '';

    if (!$webformId) {
      $statusCode = 400;
      $message = t("Missing form 'id'.");
      $this->logSubmission($statusCode, $message);
      return new ModifiedResourceResponse(['errors' => $message], $statusCode);
    }

    $values = [
      'webform_id' => $webformId,
    ];
    unset($data['id']);

    // Check if webform exists.
    $webform = Webform::load($webformId);
    if (!$webform) {
      $statusCode = 422;
      $message = t('Submission attempt against non-existent webform.');
      $this->logSubmission($statusCode, $message);
      return new ModifiedResourceResponse(['errors' => WebformSubmissionResource::INVALID_FORM_ID_ERROR], $statusCode);
    }

    $agencyComponent = $this->getAssociatedAgencyComponent($webformId);
    if (!$agencyComponent) {
      $statusCode = 422;
      $message = t('Submission attempt against webform unassociated with agency component.');
      $this->logSubmission($statusCode, $message);
      return new ModifiedResourceResponse(['errors' => WebformSubmissionResource::INVALID_FORM_ID_ERROR], $statusCode);
    }

    $isWebformAcceptingSubmissions = WebformSubmissionForm::isOpen($webform);
    if (!$isWebformAcceptingSubmissions) {
      $statusCode = 422;
      $message = t('Submission attempt against closed webform.');
      $this->logSubmission($statusCode, $message, $agencyComponent);
      return new ModifiedResourceResponse(['errors' => WebformSubmissionResource::INVALID_FORM_ID_ERROR], $statusCode);
    }

    // Check for file attachments.
    $fileAttachmentsOnSubmission = $this->getSubmittedFileAttachments($webform, $data);
    if ($fileAttachmentsOnSubmission) {
      $fileErrors = $this->validateAttachmentsContainRequiredInfo($fileAttachmentsOnSubmission);
      if (!$fileErrors) {
        $fileEntities = $this->createFileEntities($fileAttachmentsOnSubmission);
        $fileErrors = $this->validateFileEntities($webform, $fileEntities);
        $this->attachFileEntitiesToSubmission($fileEntities, $data);
      }
    }

    $values['data'] = $data;

    // Validate submission.
    $submissionErrors = WebformSubmissionForm::validateValues($values);
    $errors = $fileErrors ? array_merge((array) $submissionErrors, $fileErrors) : $submissionErrors;
    if (!empty($errors)) {
      // Delete any created attachments on invalid submissions.
      if ($fileAttachmentsOnSubmission && $fileEntities) {
        $this->deleteFilesFromTemporaryStorage($fileEntities);
      }
      $statusCode = 422;
      $message = t('Submission attempt with invalid data.');
      $this->logSubmission($statusCode, $message, $agencyComponent);
      return new ModifiedResourceResponse(['errors' => $errors], $statusCode);
    }

    // Perform submission.
    $webformSubmission = WebformSubmissionForm::submitValues($values);
    $submissionId = $webformSubmission->id();

    // If attachments were submitted, move them out of temporary storage.
    if ($fileAttachmentsOnSubmission && $fileEntities) {
      $this->moveFilesToFinalDestination($webform, $webformSubmission, $fileEntities);
    }

    $statusCode = 201;
    $message = t('Webform submission created. SID: %sid.', ['%sid' => $submissionId]);
    $this->logSubmission($statusCode, $message, $agencyComponent);
    return new ModifiedResourceResponse(['submission_id' => $submissionId], $statusCode);
  }

  /**
   * Logs a submission with HTTP status code, message, and optional component.
   *
   * @param int $statusCode
   *   HTTP status code returned to the entity submitting the webform.
   * @param string $message
   *   The message to log.
   * @param \Drupal\node\NodeInterface|null $agencyComponent
   *   The agency component the webform is being submitted to.
   */
  protected function logSubmission($statusCode, $message, NodeInterface $agencyComponent = NULL) {
    $context = [
      '%status' => $statusCode,
      '%message' => $message,
    ];
    if ($agencyComponent) {
      $this->logSubmissionWithComponent($context, $agencyComponent);
    }
    else {
      $this->logger->info("FOIA API Webform Submission: HTTP Status: %status, Message: %message", $context);
    }
  }

  /**
   * Logs a submission with agency component information.
   *
   * @param array $context
   *   An array of contextual information to log.
   * @param \Drupal\node\NodeInterface $agencyComponent
   *   The agency component the webform is being submitted to.
   */
  protected function logSubmissionWithComponent(array $context, NodeInterface $agencyComponent) {
    $context['%agency_component_id'] = $agencyComponent->id();
    $context['%agency_component'] = $agencyComponent->getTitle();
    $this->logger->info("FOIA API Webform Submission for agency component %agency_component_id - %agency_component: HTTP Status: %status, Message: %message", $context);
  }

  /**
   * Gets the loaded agency component node associated to the webform.
   *
   * @param string $webformId
   *   The ID of the webform to perform an association lookup against.
   *
   * @return \Drupal\node\NodeInterface|bool
   *   Returns the agency component that references the webform. FALSE if no
   *   component node references the form.
   */
  protected function getAssociatedAgencyComponent($webformId) {
    $query = $this->queryFactory->get('node');
    $query->condition('field_request_submission_form', $webformId);
    $nids = $query->execute();
    foreach ($nids as $nid) {
      $agencyComponent = Node::load($nid);
    }
    if ($agencyComponent) {
      return $agencyComponent;
    }
    return FALSE;
  }

  /**
   * Gets any file attachments that were included with the submission.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform being submitted against.
   * @param array $data
   *   The contents of the submission.
   *
   * @return array|bool
   *   Returns an array of file attachments keyed by webform element. An empty
   *   array if the webform has an attachments field but no attachments were
   *   submitted. FALSE if the webform has no attachments field.
   */
  protected function getSubmittedFileAttachments(WebformInterface $webform, array $data) {
    if ($webform->hasManagedFile()) {
      $fileAttachmentElementsOnWebform = $this->getFileAttachmentElementsOnWebform($webform);
      return $this->getFileAttachmentsOnSubmission($fileAttachmentElementsOnWebform, $data);
    }
    return FALSE;
  }

  /**
   * Gets the names of file attachment elements on the webform.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform being submitted against.
   *
   * @return array
   *   Returns an array of the names of the file attachment elements on the
   *   webform being submitted against.
   */
  protected function getFileAttachmentElementsOnWebform(WebformInterface $webform) {
    $elements = $webform->getElementsInitialized();
    $fileAttachmentElementKeys = [];
    foreach ($elements as $key => $element) {
      if (isset($element['#type']) && $element['#type'] == 'managed_file') {
        $fileAttachmentElementKeys[] = $key;
      }
    }
    return $fileAttachmentElementKeys;
  }

  /**
   * Gets the submitted file attachment data.
   *
   * @param array $fileAttachmentElementKeys
   *   The keys of the file attachment webform elements.
   * @param array $data
   *   The contents of the submission.
   *
   * @return array
   *   Returns an array of submitted file attachments keyed by webform element.
   */
  protected function getFileAttachmentsOnSubmission(array $fileAttachmentElementKeys, array $data) {
    $fileAttachmentsSubmitted = [];
    foreach ($fileAttachmentElementKeys as $fileAttachmentElementKey) {
      foreach ($data as $fieldName => $value) {
        if ($fileAttachmentElementKey == $fieldName) {
          $fileAttachmentsSubmitted[$fieldName] = $value;
        }
      }
    }
    return $fileAttachmentsSubmitted;
  }

  /**
   * Validates that the submitted file attachments contain required information.
   *
   * @param array $fileAttachmentsByField
   *   Submitted file attachment data keyed by field name.
   *
   * @return array
   *   Returns an array of errors keyed by attachment field name, highlighting
   *   any missing required information.
   */
  protected function validateAttachmentsContainRequiredInfo(array $fileAttachmentsByField) {
    $errors = [];
    foreach ($fileAttachmentsByField as $fieldName => $fileAttachmentsSubmitted) {
      foreach ($fileAttachmentsSubmitted as $fileAttachment) {
        $missingInfo = $this->validateAttachmentContainsRequiredInfo($fileAttachment);
        if (!empty($missingInfo)) {
          $errors[$fieldName][] = $missingInfo;
        }
      }
    }
    return $errors;
  }

  /**
   * Validates that a submitted file attachment contain required information.
   *
   * @param array $fileAttachment
   *   Submitted file attachment data for a single attachment.
   *
   * @return array
   *   Returns an array of errors highlighting any missing required information.
   */
  protected function validateAttachmentContainsRequiredInfo(array $fileAttachment) {
    $missingInfo = [];
    if (!isset($fileAttachment['filename'])) {
      $missingInfo[] = 'Attached file is missing a filename.';
    }
    if (!isset($fileAttachment['filesize'])) {
      $missingInfo[] = 'Attached file is missing a filesize.';
    }
    if (!isset($fileAttachment['content_type'])) {
      $missingInfo[] = 'Attached file is missing a content_type.';
    }
    if (!isset($fileAttachment['filedata'])) {
      $missingInfo[] = 'Attached file is missing filedata.';
    }
    return $missingInfo;
  }

  /**
   * Creates file entities given an array of submitted file attachment data.
   *
   * @param array $fileAttachmentsByField
   *   Submitted file attachment data keyed by field name.
   *
   * @return array
   *   Returns an array of file entities keyed by field name.
   */
  protected function createFileEntities(array $fileAttachmentsByField) {
    $files = [];
    foreach ($fileAttachmentsByField as $fieldName => $fileAttachmentsSubmitted) {
      foreach ($fileAttachmentsSubmitted as $fileAttachment) {
        $files[$fieldName][] = $this->createFileEntityInTempStorage($fileAttachment);
      }
    }
    return $files;
  }

  /**
   * Creates a file entity at a temporary location given file attachment data.
   *
   * @param array $fileAttachment
   *   An individual file attachment.
   *
   * @return \Drupal\file\FileInterface
   *   Returns a file entity corresponding to the given file attachment.
   */
  protected function createFileEntityInTempStorage(array $fileAttachment) {
    $fileContents = isset($fileAttachment['filedata']) ? base64_decode($fileAttachment['filedata']) : '';
    $mimeType = isset($fileAttachment['content_type']) ? $fileAttachment['content_type'] : '';
    $fileSize = isset($fileAttachment['filesize']) ? $fileAttachment['filesize'] : '';
    $fileName = isset($fileAttachment['filename']) ? $fileAttachment['filename'] : '';
    $fileUri = file_unmanaged_save_data($fileContents);
    if ($fileUri) {
      $file = File::create([
        'uri' => $fileUri,
        'uid' => \Drupal::currentUser()->id(),
        'filesize' => $fileSize,
        'filemime' => $mimeType,
        'filename' => $fileName,
      ]);
      $file->save();
      return $file;
    }
  }

  /**
   * Validates file entities against appropriate webform element settings.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform being submitted against.
   * @param array $filesByFieldName
   *   File entities keyed by webform element names.
   *
   * @return array
   *   Returns an array of validation errors keyed by the webform element's
   *   field name.
   */
  protected function validateFileEntities(WebformInterface $webform, array $filesByFieldName) {
    $errors = [];
    foreach ($filesByFieldName as $fieldName => $files) {
      $element = $webform->getElementInitialized($fieldName);
      $defaultProperties = $this->getDefaultWebformElementProperties($element);
      $maxFileSize = isset($element['#max_filesize']) ? $element['#max_filesize'] : $defaultProperties['max_filesize'];
      $maxFileSize = Bytes::toInt("{$maxFileSize}MB");
      $fileExtensions = isset($element['#file_extensions']) ? $element['#file_extensions'] : $defaultProperties['file_extensions'];
      $validators['file_validate_size'] = [$maxFileSize];
      $validators['file_validate_extensions'] = [$fileExtensions];
      /** @var \Drupal\file\FileInterface $file */
      foreach ($files as $file) {
        $validationErrors = file_validate($file, $validators);
        if (!empty($validationErrors)) {
          $errors[$fieldName][] = $validationErrors;
        }
      }
    }
    return $errors;
  }

  /**
   * Returns default properties for a webform element.
   *
   * @param array $element
   *   The webform element to get default properties for.
   *
   * @return array
   *   An associative array containing default element properties.
   */
  protected function getDefaultWebformElementProperties(array $element) {
    /** @var \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase $elementHandler */
    $elementHandler = $this->elementManager->getElementInstance($element);
    return $elementHandler->getDefaultProperties();
  }

  /**
   * Replaces submitted binary file attachments with Drupal fids.
   *
   * @param array $filesByFieldName
   *   File entities keyed by webform element names.
   * @param array &$data
   *   The contents of the submission.
   */
  protected function attachFileEntitiesToSubmission(array $filesByFieldName, array &$data) {
    foreach ($filesByFieldName as $fieldName => $files) {
      unset($data[$fieldName]);
      /** @var \Drupal\file\FileInterface $file */
      foreach ($files as $file) {
        $data[$fieldName][] = $file->id();
      }
    }
  }

  /**
   * Deletes file entities given an array of file entities keyed by file name.
   *
   * @param array $filesByFieldName
   *   Array of file entities to be deleted.
   */
  protected function deleteFilesFromTemporaryStorage(array $filesByFieldName) {
    foreach ($filesByFieldName as $fieldName => $files) {
      /** @var \Drupal\file\FileInterface $file */
      foreach ($files as $file) {
        file_delete($file->id());
      }
    }
  }

  /**
   * Moves files to their final upload destination based on webform information.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform being submitted against.
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   The webform submission that was created.
   * @param array $filesByFieldName
   *   Array of file entities keyed by webform element names.
   */
  protected function moveFilesToFinalDestination(WebformInterface $webform, WebformSubmissionInterface $webformSubmission, array $filesByFieldName) {
    foreach ($filesByFieldName as $fieldName => $files) {
      $element = $webform->getElementInitialized($fieldName);
      $defaultProperties = $this->getDefaultWebformElementProperties($element);
      $uriScheme = isset($element['#uri_scheme']) ? $element['#uri_scheme'] : $defaultProperties['uri_scheme'];

      /** @var \Drupal\file\FileInterface $file */
      foreach ($files as $file) {
        $sourceUri = $file->getFileUri();
        $destinationUri = "{$uriScheme}://webform/{$webform->id()}/{$webformSubmission->id()}";
        $destinationDirectory = $this->fileSystem->dirname($destinationUri);
        file_prepare_directory($destinationDirectory, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
        $destinationUri = file_unmanaged_move($sourceUri, $destinationUri);
        // Update the file's uri and save.
        $file->setFileUri($destinationUri);
        $file->save();

        // Set file usage which will also make the file's status permanent.
        $this->fileUsage->delete($file, 'webform', 'webform_submission', $webformSubmission->id(), 0);
        $this->fileUsage->add($file, 'webform', 'webform_submission', $webformSubmission->id());
      }
    }

  }

}
