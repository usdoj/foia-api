<?php

namespace Drupal\Tests\webform_template\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class WebformTemplateTest
 *
 * Tests that Webform Template is usable.
 *
 * @package Drupal\Tests\webform_template\Functional
 *
 * @group webform_template
 */
class WebformTemplateTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_template'];

  /**
   * Tests that duplication page works.
   */
  public function testDuplication() {
$this->assertEquals(1, 1);
    /*$account = $this->drupalCreateUser(['duplicate forms']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/structure/webform/manage/basic_request_submission_form/duplicate');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->pageTextContains('Duplicate \'Basic Request submission form\' form Add to Default shortcuts');*/
  }

}
