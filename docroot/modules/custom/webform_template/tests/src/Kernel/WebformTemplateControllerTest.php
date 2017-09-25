<?php

namespace Drupal\Tests\webform_template\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Class RequestTemplateTest.
 *
 * Tests template and duplication thereof.
 *
 * @package Drupal\Tests\webform_template\Kernel
 */
class RequestTemplateTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform_template', 'webform', 'system', 'user'];

  /**
   * Tests webform template.
   */
  public function testAddDefaultFields() {

    $this->installConfig(['webform', 'webform_template']);

    // Gets template elements from module config.
    $config = \Drupal::config('webform_template.settings')->get('webform_template_elements');
    $templateElements = yaml_parse($config);

    // Creates webform and specifies to use the template fields.
    $webformWithTemplate = Webform::create(['id' => 'webform_with_template']);
    $webformWithTemplate->set('foia_template', 1);
    $webformWithTemplate->save();

    // Creates additional webform and specifies NOT to use the template fields.
    $webformWithoutTemplate = Webform::create(['id' => 'webform_without_template']);
    $webformWithoutTemplate->set('foia_template', 0);
    $webformWithoutTemplate->save();

    // Tests that webforms are being created as expected.
    $this->assertEquals('webform_with_template', $webformWithTemplate->id());
    $this->assertEquals('webform_without_template', $webformWithoutTemplate->id());
    $this->assertTrue($webformWithTemplate->isOpen());
    $this->assertTrue($webformWithoutTemplate->isOpen());

    // Tests the webform was created with the elements from the template.
    $webformWithTemplate = Webform::load('webform_with_template');
    $this->assertEquals($templateElements, $webformWithTemplate->getElementsDecoded());

    // Tests the webform was created without the elements from the template.
    $webformWithoutTemplate = Webform::load('webform_without_template');
    $this->assertNotEquals($templateElements, $webformWithoutTemplate->getElementsDecoded());

  }

}
