<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for FOIA Webform Kernel Tests.
 */
abstract class FoiaWebformKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'field_permissions',
    'foia_webform',
    'node',
    'system',
    'taxonomy',
    'text',
    'user',
    'webform',
    'webform_template',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('webform', ['webform']);
    $this->installConfig(['webform', 'webform_template', 'foia_webform']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');

  }

}
