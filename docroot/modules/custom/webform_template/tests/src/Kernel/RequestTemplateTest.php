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
  public function testRequestTemplate() {

    $this->installConfig(['webform', 'webform_template']);

    // Creates foia_template.
    $config = \Drupal::config('webform_template.settings')->get('webform_template_elements');
    $config = yaml_parse($config);
    $foia_template = Webform::create(['id' => 'foia_template']);
    $foia_template->setElements($config);
    $foia_template->set('template', TRUE);
    $foia_template->save();

    // Creates webform.
    $webform = Webform::create(['id' => 'template_test']);
    $webform->set('foia_template', [
      '#type' => 'checkbox',
      '#title' => t("Use FOIA Agency template"),
      '#disabled' => TRUE,
      '#default_value' => 'foia_template',
      '#value' => 'foia_template',
    ]);
    $webform->save();

    // Initial tests.
    $this->assertEquals('template_test', $webform->id());
    $this->assertTrue($webform->isOpen());
    $this->assertFalse($webform->isTemplate());
    $this->assertTrue($foia_template->isTemplate());

    // Tests that elements are the same.
    $template_test = Webform::load('template_test');
    $this->assertEquals($foia_template->getElementsDecoded(), $template_test->getElementsDecoded());

  }

}
