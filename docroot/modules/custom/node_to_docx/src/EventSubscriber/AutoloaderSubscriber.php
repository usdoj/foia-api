<?php

namespace Drupal\node_to_docx\EventSubscriber;

use Phpdocx\AutoLoader;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Logger\RfcLogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 *
 */
class AutoloaderSubscriber implements EventSubscriberInterface {
  /**
   * @var bool
   */
  protected $autoloader_registered = FALSE;

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
  public function onRequest(GetResponseEvent $event) {
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
    if (!$this->autoloader_registered) {
      // If the class can already be loaded, do nothing.
      if (class_exists('Phpdocx\\Create\\CreateDocx')) {
        $this->autoloader_registered = TRUE;
        return;
      }
      $filepath = $this->getAutoloadFilepath();
      if (!is_file($filepath)) {
        throw new \RuntimeException(SafeMarkup::format('Autoloader not found: @filepath', ['@filepath' => $filepath]));
      }
      if (($filepath != DRUPAL_ROOT . '/core/vendor/autoload.php')) {
        $this->autoloader_registered = TRUE;
        require $filepath;
        AutoLoader::load();
      }
    }
  }

  /**
   * Returns the absolute path to the AutoLoad.php file. If AutoLoad.php does not exist return false.
   *
   * @return string
   */
  public function getAutoloadFilepath() {
    $module_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'node_to_docx') . '/phpdocx/Classes/Phpdocx/AutoLoader.php';
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
