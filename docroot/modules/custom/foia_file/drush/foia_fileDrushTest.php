<?php

namespace Unish;

if (class_exists('Unish\CommandUnishTestCase')) {

  /**
   * Class foia_fileDrushTest.
   *
   * PHPUnit Tests for foia_file. This uses Drush's own test framework, based on
   * PHPUnit. To run the tests, use run-tests-drush.sh from the devel directory.
   *
   * @package Drupal\Tests\foia_webform\Unish
   */
  class FoiaFileCase extends CommandUnishTestCase {

    /**
     * Tests drush commands for the foia_file module.
     */
    public function testFoiaFileCommands() {

      // Specify Drupal 8.
      $sites = $this->setUpDrupal(1, TRUE, '8');

      // Symlink this module in site so it can be enabled.
      $target = dirname(__DIR__);
      \symlink($target, $this->webroot() . '/modules/custom/foia_file');
      $options = [
        'root' => $this->webroot(),
        'uri' => key($sites),
      ];
      $this->drush('pm-enable', ['foia_file'], $options + ['skip' => NULL, 'yes' => NULL]);

      $this->drush('file-entity-update', ['/var/www/files/test.txt: Eicar-Test-Signature FOUND\n/var/www/files/test.txt: Removed.\n/var/www/files/another.test.txt: OK\n/var/www/files/test.txt.txt: OK'], $options);

      $output = $this->getOutput();
      $this->assertContains('File Entity update started.', $output, 'Output contains "File Entity update started."');

    }

  }

}
