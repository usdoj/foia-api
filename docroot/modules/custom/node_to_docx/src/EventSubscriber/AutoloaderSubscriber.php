<?php

namespace Drupal\node_to_docx\EventSubscriber;

use Drupal\Core\Logger\RfcLogLevel;
use Phpdocx\AutoLoader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class AutoloaderSubscriber - register with the Autoloader.
 */
class AutoloaderSubscriber implements EventSubscriberInterface {
  /**
   * Checks if autoloader is registered.
   *
   * @var bool
   */
  protected $autoloaderRegistered = FALSE;

  /**
   * Implements \Symfony\Component\EventDispatcher\EventSubscriberInterface::getSubscribedEvents().
   */
  public static function getSubscribedEvents() {
    return [
      // Run very early but after composer_manager which has a priority of 999.
      KernelEvents::REQUEST => ['onRequest', 990],
    ];
  }

  /**
   * Registers the autoloader.
   */
  public function onRequest(RequestEvent $event) {
    try {
      $this->registerAutoloader();
    }
    catch (\RuntimeException $e) {
      if (PHP_SAPI !== 'cli') {
        watchdog_exception('node_to_docx', $e, NULL, [], RfcLogLevel::WARNING);
      }
    }
  }

  /**
   * Registers the autoloader.
   *
   * @throws \RuntimeException
   */
  public function registerAutoloader() {
    if (!$this->autoloaderRegistered) {
      // If the class can already be loaded, do nothing.
      if (class_exists('Phpdocx\\Create\\CreateDocx')) {
        $this->autoloaderRegistered = TRUE;
        return;
      }
      $filepath = $this->getAutoloadFilepath();
      if (!is_file($filepath)) {
        \Drupal::logger('node_to_docx')->warning(
          "Autoloader not found: @filepath",
          [
            '@filepath' => $filepath,
          ]
        );
        return;
      }
      if (($filepath != DRUPAL_ROOT . '/core/vendor/autoload.php')) {
        $this->autoloaderRegistered = TRUE;
        require $filepath;
        AutoLoader::load();
      }
    }
  }

  /**
   * Returns the absolute path to the AutoLoad.php file.
   *
   * If AutoLoad.php does not exist return false.
   *
   * @return string
   *   The path to AutoLoad.php file.
   */
  public function getAutoloadFilepath() {
    $module_path = DRUPAL_ROOT . '/' . \Drupal::service('extension.list.module')->getPath('node_to_docx') . '/phpdocx/Classes/Phpdocx/AutoLoader.php';
    $library_path = DRUPAL_ROOT . '/libraries/phpdocx/Classes/Phpdocx/AutoLoader.php';

    if (file_exists($library_path)) {
      return $library_path;
    }
    elseif (file_exists($module_path)) {
      return $module_path;
    }

    return FALSE;
  }

}
