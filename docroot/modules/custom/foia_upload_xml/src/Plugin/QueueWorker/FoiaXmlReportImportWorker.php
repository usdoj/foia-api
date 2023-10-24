<?php

namespace Drupal\foia_upload_xml\Plugin\QueueWorker;

use Drupal\Core\File\Exception\FileNotExistsException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\RequeueException;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;

/**
 * Import uploaded report xml files on cron.
 *
 * @QueueWorker(
 *   id = "foia_xml_report_import_worker",
 *   title = @Translation("FOIA Xml Report Importer"),
 *   cron = {"time" = 600}
 * )
 */
class FoiaXmlReportImportWorker extends QueueWorkerBase {

  /**
   * The list of migrations to run.
   *
   * @var \Drupal\foia_upload_xml\FoiaUploadXmlMigrationsProcessor
   */
  protected $migrationsProcessor;

  /**
   * Process a foia_xml_report_import_worker queue item.
   *
   * @param mixed $data
   *   The queue item to process.
   *
   * @throws \Exception
   */
  public function processItem($data) {
    $lock = \Drupal::service('lock.persistent');
    if (!$lock->acquire('foia_upload_xml', 3600)) {
      throw new RequeueException('The foia_upload_xml lock could not be acquired.');
    }

    $this->migrationsProcessor = \Drupal::service('foia_upload_xml.migrations_processor');

    try {
      $source = File::load($data->fid);
      if (!$source) {
        throw new FileNotExistsException();
      }

      $user = User::load($data->uid);
      $this->migrationsProcessor
        ->setSourceFile($source)
        ->setUser($user)
        ->processAll();

      $source->delete();
    }
    catch (FileNotExistsException $e) {
      throw new \Exception(t('Could not load file @fid when attempting to process the annual report queue.  Agency: @agency Year: @year', [
        '@fid' => $data->fid,
        '@agency' => $data->agency,
        '@year' => $data->report_year,
      ]));
    }
    catch (\Exception $e) {
      throw new \Exception(t('Attempt to process file @fid in the annual report queue failed.  Agency: @agency Year: @year', [
        '@fid' => $data->fid,
        '@agency' => $data->agency,
        '@year' => $data->report_year,
      ]));
    }
    finally {
      \Drupal::service('lock.persistent')->release('foia_upload_xml');
    }
  }

}
