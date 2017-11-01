<?php

namespace Drupal\Tests\foia_webform\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for foia webform tests.
 */
abstract class FoiaWebformKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'field_permissions',
    'foia_request',
    'foia_webform',
    'link',
    'node',
    'options',
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
  }

}
