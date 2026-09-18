<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams validated component CSVs into request counts for Exemption 3.
 */
final class StatuteAggregator {

  /**
   * Statute labels copied verbatim from ID_Statute_exemption_3.txt.
   *
   * The source file is not needed at runtime. Code 77 uses Column F instead.
   */
  public const STATUTES = [
    1 => '52 U.S.C. § 30109(a)(12)(A) (formerly at 2 U.S.C. § 437g(a)(12)(A))',
    2 => '5 U.S.C. §§ 7114(b)(4), 7132',
    3 => '5 U.S.C. app. 4 § 107',
    4 => '7 U.S.C. § 12',
    5 => '7 U.S.C. § 136i-1',
    6 => '8 U.S.C. § 1202(f)',
    7 => '8 U.S.C. § 1158',
    8 => '10 U.S.C. § 130(a)',
    9 => '10 U.S.C. § 130b',
    10 => '10 U.S.C. § 130c',
    11 => '10 U.S.C. § 424',
    12 => '10 U.S.C. § 1102(f)',
    13 => '10 U.S.C. § 3309 (formerly at 10 U.S.C. § 2305(g))',
    14 => '13 U.S.C. §§ 8(b), 9(a)',
    15 => '13 U.S.C. § 301(g)',
    16 => '15 U.S.C. §§ 46(f), 57b-2(f)',
    17 => '15 U.S.C. §§ 2055(a)(2), 2055(b)(1), 2055(b)(5)',
    18 => '15 U.S.C. § 6801',
    19 => '15 U.S.C. § 3710a(c)',
    20 => '15 U.S.C. § 18a(h)',
    21 => '15 U.S.C. § 1314(g)',
    22 => '15 U.S.C. § 4305(d)',
    23 => '16 U.S.C. § 470hh',
    24 => '54 U.S.C. § 100707 (formerly at 16 U.S.C. § 5937)',
    25 => '18 U.S.C. § 701',
    26 => '18 U.S.C. § 4208(c)',
    27 => '18 U.S.C. § 798',
    28 => '18 U.S.C. §§, 2510, et seq.',
    29 => '18 U.S.C. § 3123(d)',
    30 => '18 U.S.C. § 3153',
    31 => '18 U.S.C. § 3509(d)',
    32 => '18 U.S.C. § 3521(b)(1)(g)',
    33 => '18 U.S.C. § 5038',
    34 => '19 U.S.C. §§ 2605(h), 2605(i)',
    35 => '19 U.S.C. § 1677f',
    36 => '21 U.S.C. § 331(j)',
    37 => '22 U.S.C. § 1644',
    38 => '22 U.S.C. §§ 1461, 1461-1a',
    39 => '22 U.S.C. § 2778(e)',
    40 => '22 U.S.C. § 3104(c)',
    41 => '26 U.S.C. §§ 6103, 6',
    42 => '26 U.S.C. § 7123',
    43 => '28 U.S.C. § 652(d)',
    44 => '31 U.S.C. § 5311',
    45 => '31 U.S.C. § 3730',
    46 => '31 U.S.C. § 5319',
    47 => '35 U.S.C. § 122',
    48 => '38 U.S.C. § 5705',
    49 => '38 U.S.C. § 7332',
    50 => '39 U.S.C. § 410(c)(2)',
    51 => '41 U.S.C. § 4702 (formerly at 41 U.S.C. § 253b(m)(1))',
    52 => '41 U.S.C. § 2102 (formerly at 41 U.S.C. § 423(a)(1))',
    53 => '42 U.S.C. § 300aa12(D)(4)(A)',
    54 => '42 U.S.C. §§ 2000e-5b, 2008e-8(e)',
    55 => '42 U.S.C. § 2000g-2b',
    56 => '34 U.S.C. § 10231 (formerly at 42 U.S.C. § 3789g)',
    57 => '34 U.S.C. § 12592 (formerly at 42 U.S.C. § 14132(b)(3))',
    58 => '42 U.S.C. § 405(r)',
    59 => '42 U.S.C. §§ 2162, 2167, 2168(a)(1)',
    60 => '42 U.S.C. §§ 2286d(b), 2286d(h)(3)',
    61 => '42 U.S.C. § 3610(d)',
    62 => '45 U.S.C. § 362(d)',
    63 => '47 U.S.C. § 605',
    64 => '49 U.S.C. § 114(r) (formerly at 40 U.S.C. § 114(s))',
    65 => '49 U.S.C. § 1114(c)',
    66 => '[Statute repealed, cite to ID 64]',
    67 => '50 U.S.C. § 3605 (formerly at 50 U.S.C. § 402 note)',
    68 => '50 U.S.C. § 3507 (formerly at 50 U.S.C. § 403g)',
    69 => '50 U.S.C. § 3024(i)(1) (formerly at 50 U.S.C. § 403-1(i)(1))',
    70 => '50 U.S.C. § 3141(a) (formerly at 50 U.S.C. § 432 )',
    71 => '50 U.S.C. § 3143 (formerly at 50 U.S.C. § 432a)',
    72 => '50 U.S.C. § 1702(a)(1)',
    73 => 'Pub. L. No. 115-232',
    74 => 'Fed. R. Crim. P. 6(e)',
    75 => '[Do not use]',
    76 => 'Pub. L. No. 111-8, 123 Stat. 524',
    77 => '[Statute not listed]',
  ];

  /**
   * Aggregates one file at a time without retaining request rows.
   *
   * @param array $sources
   *   Component/file pairs, each with component_id and uri keys.
   *
   * @return array
   *   Statute descriptions, withheld text, citations, and component counts.
   */
  public function aggregate(array $sources): array {
    $statutes = [];
    foreach ($sources as $source) {
      $stream = @fopen($source['uri'], 'rb');
      if ($stream === FALSE) {
        throw new \RuntimeException('Unable to reopen a validated CSV for statute aggregation.');
      }
      try {
        $header = TRUE;
        while (($columns = fgetcsv($stream, 0, ',', '"', '')) !== FALSE) {
          if ($columns === [NULL]) {
            continue;
          }
          if (count($columns) !== CsvValidator::EXPECTED_COLUMNS) {
            throw new \RuntimeException('CSV column count changed after validation.');
          }
          if ($header) {
            $header = FALSE;
            continue;
          }
          if (trim($columns[4]) === '') {
            continue;
          }
          // One request counts once per statute, even if its ID is repeated.
          $ids = array_unique(array_map('intval', explode(',', $columns[4])));
          foreach ($ids as $id) {
            if (!isset(self::STATUTES[$id])) {
              throw new \RuntimeException('CSV contains an unknown statute ID after validation.');
            }
            // Other statutes must remain distinct rather than sharing ID 77's
            // generic label. Identical trimmed descriptions share one entry.
            $description = $id === 77 ? trim($columns[5]) : self::STATUTES[$id];
            $key = $id === 77 ? '77:' . $description : (string) $id;
            if (!isset($statutes[$key])) {
              $statutes[$key] = [
                'description' => $description,
                'information_withheld' => [],
                'citations' => [],
                'counts' => [],
              ];
            }
            $component_id = $source['component_id'];
            $statutes[$key]['counts'][$component_id] = ($statutes[$key]['counts'][$component_id] ?? 0) + 1;
            // Preserve distinct G and H values without retaining their rows.
            $withheld = trim($columns[6]);
            if ($withheld !== '') {
              $statutes[$key]['information_withheld'][$withheld] = $withheld;
            }
            $citation = trim($columns[7]);
            if ($citation !== '') {
              $statutes[$key]['citations'][$citation] = $citation;
            }
          }
        }
        if (!feof($stream)) {
          throw new \RuntimeException('Unable to finish reading a CSV for statute aggregation.');
        }
      }
      finally {
        fclose($stream);
      }
    }
    ksort($statutes, SORT_NATURAL);
    return $statutes;
  }

}
