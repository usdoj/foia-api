<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks request other-reason XML retains components with zero denials.
 */
#[Group('foia_raw_data_to_report')]
class OtherDenialReasonXmlTest extends UnitTestCase {

  /**
   * Emits explicit N/A entries, existing reasons, totals, and associations.
   */
  public function testEmptyComponents(): void {
    $reason = ['description' => 'Example reason', 'quantity' => 2];
    foreach ([FALSE, TRUE] as $all_empty) {
      $statistics = [
        'components' => [10 => [], 20 => $all_empty ? [] : [$reason]],
        'overall' => $all_empty ? [] : [$reason],
      ];
      $document = new \DOMDocument();
      $root = $document->createElement('report');
      $document->appendChild($root);
      $builder = new XmlReportBuilder();
      $map = [10 => 'ORG1', 20 => 'ORG2'];
      $method = new \ReflectionMethod($builder, 'addOtherDenialReasons');
      $method->invoke($builder, $document, $root, $statistics, $map, 'RequestDenialOtherReasonSection', 'CODR');
      $method->invoke($builder, $document, $root, $statistics, $map, 'AppealDenialOtherReasonSection', 'ADOR');
      $xpath = new \DOMXPath($document);
      $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
      $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
      $xpath->registerNamespace('nc', 'http://niem.gov/niem/niem-core/2.0');
      $section = '//foia:RequestDenialOtherReasonSection';
      $this->assertSame(3, $xpath->query($section . '/foia:ComponentOtherDenialReason')->length);
      foreach ([0, 1, 2] as $id) {
        $empty = $all_empty || $id === 1;
        $base = $section . '/foia:ComponentOtherDenialReason[@s:id="CODR' . $id . '"]';
        $this->assertSame(1, $xpath->query($base . '/foia:OtherDenialReason')->length);
        $this->assertSame($empty ? 'N/A' : 'Example reason', $xpath->evaluate('string(' . $base . '/foia:OtherDenialReason/foia:OtherDenialReasonDescriptionText)'));
        $this->assertSame($empty ? '0' : '2', $xpath->evaluate('string(' . $base . '/foia:OtherDenialReason/foia:OtherDenialReasonQuantity)'));
        $this->assertSame($empty ? '0' : '2', $xpath->evaluate('string(' . $base . '/foia:ComponentOtherDenialReasonQuantity)'));
        $association = $section . '/foia:OtherDenialReasonOrganizationAssociation[foia:ComponentDataReference/@s:ref="CODR' . $id . '"]';
        $this->assertSame('ORG' . $id, $xpath->evaluate('string(' . $association . '/nc:OrganizationReference/@s:ref)'));
      }
      $this->assertSame(0, $xpath->query('//foia:AppealDenialOtherReasonSection/foia:ComponentOtherDenialReason[@s:id="ADOR1"]/foia:OtherDenialReason')->length);
    }
  }

}
