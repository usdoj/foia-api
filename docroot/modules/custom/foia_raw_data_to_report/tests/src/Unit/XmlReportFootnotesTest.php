<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Checks empty section footnotes match the latest example structure.
 */
#[Group('foia_raw_data_to_report')]
class XmlReportFootnotesTest extends UnitTestCase {

  /**
   * Adds footnotes after organization associations in the selected sections.
   */
  public function testSectionFootnotes(): void {
    $document = new \DOMDocument();
    $root = $document->createElement('report');
    $document->appendChild($root);
    $builder = new XmlReportBuilder();
    $counts = ['pending_start' => 0, 'received' => 0, 'processed' => 0, 'pending_end' => 0];
    $statistics = ['components' => [1 => $counts], 'overall' => $counts];
    $map = [1 => 'ORG1'];
    foreach (['ProcessedRequestSection', 'ProcessedConsultationSection', 'ProcessedAppealSection'] as $name) {
      (new \ReflectionMethod($builder, 'addProcessingStatistics'))->invoke($builder, $document, $root, $statistics, $map, $name, 'PS');
    }
    (new \ReflectionMethod($builder, 'addPersonnelAndCost'))->invoke($builder, $document, $root, $map, []);
    $xpath = new \DOMXPath($document);
    $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
    foreach (['ProcessedRequestSection', 'ProcessedConsultationSection', 'PersonnelAndCostSection'] as $name) {
      $this->assertSame(1, $xpath->query('//foia:' . $name . '/foia:FootnoteText')->length);
      $last = $xpath->query('//foia:' . $name . '/*[last()]')->item(0);
      $this->assertSame('FootnoteText', $last->localName);
      $this->assertSame('', $last->textContent);
    }
    $this->assertSame(0, $xpath->query('//foia:ProcessedAppealSection/foia:FootnoteText')->length);
  }

}
