<?php

namespace Drupal\Tests\foia_request\Kernel;

use Drupal\foia_request\Entity\FoiaRequest;
use Drupal\foia_request\Entity\FoiaRequestInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Class FoiaRequestTest.
 *
 * @package Drupal\Tests\foia_request\Kernel
 */
class FoiaRequestTest extends EntityKernelTestBase {

  /**
   * Array of modules required for FOIA Request testing.
   *
   * @var array
   */
  public static $modules = ['foia_request', 'options', 'field_permissions'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installConfig('system');
    $this->installEntitySchema('foia_request');
  }

  /**
   * Tests FOIA Requests are created with appropriate defaults.
   */
  public function testFoiaRequest() {
    $foiaRequest = FoiaRequest::create();

    $this->assertEquals('foia_request', $foiaRequest->getEntityTypeId());
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());
    $this->assertNotEmpty($foiaRequest->get('created')->value);
  }

  /**
   * Tests that invalid request status becomes default and submitted passes.
   */
  public function testSetRequestStatus() {
    $foiaRequest = FoiaRequest::create();

    $foiaRequest->setRequestStatus(5);
    $this->assertEquals(FoiaRequestInterface::STATUS_QUEUED, $foiaRequest->getRequestStatus());

    $foiaRequest->setRequestStatus(FoiaRequestInterface::STATUS_SUBMITTED);
    $this->assertEquals(FoiaRequestInterface::STATUS_SUBMITTED, $foiaRequest->getRequestStatus());
  }

}
