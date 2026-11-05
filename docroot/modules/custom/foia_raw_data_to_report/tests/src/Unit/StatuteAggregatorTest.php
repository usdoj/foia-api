<?php

namespace Drupal\Tests\foia_raw_data_to_report\Unit;

use Drupal\foia_raw_data_to_report\StatuteAggregator;
use Drupal\foia_raw_data_to_report\XmlReportBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests embedded statute details and custom statute isolation.
 */
#[CoversClass(StatuteAggregator::class)]
#[Group('foia_raw_data_to_report')]
class StatuteAggregatorTest extends UnitTestCase {

  /**
   * Mixed codes use embedded details without leaking custom text to them.
   */
  public function testMixedStatutes(): void {
    $path = tempnam(sys_get_temp_dir(), 'foia-statutes-');
    try {
      $stream = fopen($path, 'wb');
      fputcsv($stream, array_fill(0, 29, 'column'), ',', '"', '');
      foreach ([
        ['1,1,77', ' Custom statute ', ' Custom withheld ', ' Custom citation '],
        ['1,77', 'Custom statute', 'Second withheld', 'Second citation'],
        ['7,77', 'Different statute', 'Different withheld', 'Different citation'],
        [
          '1,1,77,77', '"Custom statute","Different statute"',
          '"Third, withheld","Different withheld"',
          '"Third ""quoted"" citation","Different citation"',
        ],
        [
          '77,77', '"Custom statute","Custom statute"',
          '"Custom withheld","Custom withheld"',
          '"Custom citation","Custom citation"',
        ],
      ] as $values) {
        $row = array_fill(0, 29, '');
        foreach ($values as $offset => $value) {
          $row[4 + $offset] = $value;
        }
        fputcsv($stream, $row, ',', '"', '');
      }
      fclose($stream);
      $statutes = (new StatuteAggregator())->aggregate([
        ['component_id' => 10, 'uri' => $path],
      ]);
      $this->assertCount(4, $statutes);
      $this->assertSame(StatuteAggregator::STATUTES[1]['description'], $statutes[1]['description']);
      $this->assertSame([StatuteAggregator::STATUTES[1]['information_withheld']], array_values($statutes[1]['information_withheld']));
      $this->assertSame([StatuteAggregator::STATUTES[1]['citation']], array_values($statutes[1]['citations']));
      $this->assertSame([10 => 3], $statutes[1]['counts']);
      $this->assertSame([], $statutes[7]['information_withheld']);
      $this->assertSame([], $statutes[7]['citations']);
      $custom = $statutes['77:Custom statute'];
      $this->assertSame('Custom statute', $custom['description']);
      $this->assertSame(['Custom withheld', 'Second withheld', 'Third, withheld'], array_values($custom['information_withheld']));
      $this->assertSame(['Custom citation', 'Second citation', 'Third "quoted" citation'], array_values($custom['citations']));
      $this->assertSame([10 => 5], $custom['counts']);
      $this->assertSame([10 => 2], $statutes['77:Different statute']['counts']);
      $document = new \DOMDocument();
      $root = $document->createElement('report');
      $document->appendChild($root);
      $builder = new XmlReportBuilder();
      (new \ReflectionMethod($builder, 'addStatutes'))->invoke($builder, $document, $root, $statutes, [10 => 'ORG1']);
      $xpath = new \DOMXPath($document);
      $xpath->registerNamespace('foia', 'http://leisp.usdoj.gov/niem/FoiaAnnualReport/extension/1.03');
      $xpath->registerNamespace('j', 'http://niem.gov/niem/domains/jxdm/4.1');
      $xpath->registerNamespace('s', 'http://niem.gov/niem/structures/2.0');
      $xpath->registerNamespace('nc', 'http://niem.gov/niem/niem-core/2.0');
      $this->assertSame(4, $xpath->query('//foia:ReliedUponStatute')->length);
      $this->assertSame('Custom statute', $xpath->evaluate('string(//foia:ReliedUponStatute[@s:id="ES3"]/j:StatuteDescriptionText)'));
      $this->assertSame('Different statute', $xpath->evaluate('string(//foia:ReliedUponStatute[@s:id="ES4"]/j:StatuteDescriptionText)'));
      $this->assertSame("Custom citation\nSecond citation\nThird \"quoted\" citation", $xpath->evaluate('string(//foia:ReliedUponStatute[@s:id="ES3"]/nc:Case/nc:CaseTitleText)'));

      // Sorted definitions place the two custom statutes after IDs 1 and 7.
      foreach (['ES3' => '5', 'ES4' => '2'] as $id => $quantity) {
        $association = '//foia:ReliedUponStatuteOrganizationAssociation[foia:ComponentDataReference/@s:ref="' . $id . '"]';
        foreach (['ORG0', 'ORG1'] as $organization) {
          $this->assertSame($quantity, $xpath->evaluate('string(' . $association . '[nc:OrganizationReference/@s:ref="' . $organization . '"]/foia:ReliedUponStatuteQuantity)'));
        }
      }
      $this->assertSame("Custom withheld\nSecond withheld\nThird, withheld", $xpath->evaluate('string(//foia:ReliedUponStatute[@s:id="ES3"]/foia:ReliedUponStatuteInformationWithheldText)'));
    }
    finally {
      unlink($path);
    }
  }

}
