<?php

namespace Drupal\foia_raw_data_to_report;

/**
 * Streams validated component CSVs into request counts for Exemption 3.
 */
final class StatuteAggregator {

  /**
   * Statute details embedded from Exemption 3 Codes (003).csv.
   *
   * Continuation rows in the source citations are joined with spaces.
   * Codes 66, 75, and 77 retain their original placeholder descriptions.
   * The source file is not needed at runtime. Code 77 uses Columns F-H instead.
   */
  public const STATUTES = [
    1 => [
      'description' => '52 U.S.C. § 30109(a)(12)(A) (formerly at 2 U.S.C. § 437(a)(12)(A))',
      'information_withheld' => 'Any notification or investigation made under this section of the Federal Election Campaign Act pertaining to federal campaign funds',
      'citation' => 'Gov\'t Accountability Project v. FDA, 206 F. Supp. 3d 420, 433 (D.D.C. 2016)',
    ],
    2 => [
      'description' => '5 U.S.C. §§ 7114(b)(4), 7132',
      'information_withheld' => 'Certain labor relations training and guidance materials',
      'citation' => 'Dubin v. Dep\'t of Treasury, 555 F. Supp. 408, 412 (N.D. Ga. 1981) (5 U.S.C. § 7114(b)(4)), aff\'d, 697 F.2d 1093 (11th Cir. 1983) (unpublished table decision); NTEU v. OPM, No. 76-695, slip op. at 3-4 (D.D.C. July 9, 1979) (5 U.S.C. § 7114(b) (4) and 5 U.S.C. § 7132).',
    ],
    3 => [
      'description' => '5 U.S.C. § 13109(a)(2) (formerly at 5 U.S.C. app. 4 § 107(a)(2))',
      'information_withheld' => 'Confidential financial disclosure report pertaining to certain government employees',
      'citation' => 'Meyerhoff v. EPA, 958 F.2d 1498, 1500- 02 (9th Cir. 1992); Boyd v. Exec. Off. for U.S. Attys, 161 F. Supp. 3d 1, 7 (D.D.C. 2015); Seife v. NIH, 874 F. Supp. 2d 248, 254 (S.D.N.Y. 2012).',
    ],
    4 => [
      'description' => '7 U.S.C. § 12(a)(1)',
      'information_withheld' => '"[D]ata and information that would separately disclose the business transactions of any person" and trade secrets or names of customers gathered in the course of the Commission\'s investigations under the Commodity Exchange Act"',
      'citation' => 'Hunt v. Commodity Futures Trading Comm\'n, 484 F. Supp. 47, 49 (D.D.C. 1979).',
    ],
    5 => [
      'description' => '7 U.S.C. § 136i-1(b)',
      'information_withheld' => '"[D]ata, including the location from which the data was derived, that would directly or indirectly reveal the identity of individual producers [of certain pesticides]"',
      'citation' => 'Doe v. Veneman, 380 F.3d 807, 817 (5th Cir. 2004).',
    ],
    6 => [
      'description' => '8 U.S.C. § 1202(f)',
      'information_withheld' => 'Certain records pertaining to the issuance or refusal of visas to enter the United States',
      'citation' => 'Medina-Hincapie v. Dep\'t of State, 700 F.2d 737, 741-42 (D.C. Cir. 1983); De Laurentiis v. Haig, 686 F.2d 192, 194 (3d Cir. 1982); Assadi v. Dep\'t of State, No. 12-1111, 2014 WL 4704840, at *6-7 (S.D.N.Y. Sept. 19, 2014); Badalamenti v. U.S. Dep\'t of State, 899 F. Supp. 542, 547 (D. Kan. 1995).',
    ],
    7 => [
      'description' => '8 U.S.C. § 1158',
      'information_withheld' => '',
      'citation' => '',
    ],
    8 => [
      'description' => '10 U.S.C. § 130(a)',
      'information_withheld' => 'Certain "technical data with military or space application in the possession of, or under the control of, the Department of Defense"',
      'citation' => 'Newport Aeronautical Sales v. Dep\'t of the Air Force, 684 F.3d 160, 167-68 (D.C. Cir. 2012); Chenkin v. Dep\'t of the Army, No. 93-494, 1994 U.S. Dist. LEXIS 20907, at *8 (M.D. Pa. Jan. 14, 1994), aff\'d, 61 F.3d 894 (3d Cir. 1995) (unpublished table decision).',
    ],
    9 => [
      'description' => '10 U.S.C. § 130b',
      'information_withheld' => 'Personally identifiable information pertaining to "any member of the armed forces assigned to an overseas unit, a sensitive unit, or a routinely deployable unit" and "any employee of the Department of Defense or of the Coast Guard whose duty station is with any such unit"',
      'citation' => 'Freedom Watch, Inc. v. NSA, 197 F. Supp. 3d 165, 174 (D.D.C. 2016); Hiken v. DOD, 521 F. Supp. 2d 1047, 1062 (N.D. Cal. 2007); O\'Keefe v. DOD, 463 F. Supp. 2d 317, 325 (E.D.N.Y. 2006); Windel v. United States, No. A02-306, 2005 WL 846206, at *2 (D. Alaska Apr. 11, 2005).',
    ],
    10 => [
      'description' => '10 U.S.C. § 130c',
      'information_withheld' => 'Certain "sensitive information of foreign governments" and certain international organizations',
      'citation' => 'ACLU v. DOD, 389 F. Supp. 2d 547, 553-54 (S.D.N.Y. 2005); Nat\'l Inst. of Mil. Just. v. DOD, 404 F. Supp. 2d 325, 335-37 (D.D.C. 2005), aff\'d on other grounds, 512 F.3d 677 (D.C. Cir. 2008); Gerstein v. DOD, No. 03-5193, slip op. at 8 (N.D. Ca. Dec. 21, 2004).',
    ],
    11 => [
      'description' => '10 U.S.C. § 424',
      'information_withheld' => 'Organization or any function of, and certain information pertaining to, employees of the Defense Intelligence Agency, the National Reconnaissance Office, and the National Geospatial-Intelligence Agency',
      'citation' => 'Hamdan v. DOJ, 797 F.3d 759, 776 (9th Cir. 2015); Freedom Watch, Inc. v. NSA, 197 F. Supp. 3d 165, 174 (D.D.C. 2016); Wickwire Gavin, P.C. v. Def. Intel. Agency, 330 F. Supp. 2d 592, 602 (E.D. Va. 2004).',
    ],
    12 => [
      'description' => '10 U.S.C. § 1102(f)',
      'information_withheld' => 'Medical quality assurance records created by or for the Department of Defense as part of a medical quality assurance program',
      'citation' => 'Goodrich v. Dep\'t of the Air Force, 404 F. Supp. 2d 48, 50-51 (D.D.C. 2005); Dayton Newspapers, Inc. v. Dep\'t of the Air Force, 107 F. Supp. 2d 912, 917 (S.D. Ohio 1999).',
    ],
    13 => [
      'description' => '10 U.S.C. § 3309 (formerly at 10 U.S.C. § 2305(g))',
      'information_withheld' => 'Certain contractor proposals',
      'citation' => 'Roman v. NSA, Nos. 09-2947, 09-4281, 09-3344, 09-2504, 09-5633, 2012 WL 569747, at *7 (E.D.N.Y. Feb. 22, 2012); Margolin v. NASA, No. 09-CV-00421-LRHVPC, 2011 WL 1303221, at *6 (D. Nev. Mar. 31, 2011).',
    ],
    14 => [
      'description' => '13 U.S.C. §§ 8(b), 9(a)',
      'information_withheld' => 'Certain census data',
      'citation' => 'Baldrige v. Shapiro, 455 U.S. 345, 354-55 (1982).',
    ],
    15 => [
      'description' => '13 U.S.C. § 301(g)',
      'information_withheld' => 'Certain shippers\' export declarations or successor documents',
      'citation' => 'Afr. Fund v. Mosbacher, No. 92 Civ. 289, 1993 WL 183736, at *5 (S.D.N.Y. May 26, 1993); Young Conservative Found., Inc. v. U.S. Dep\'t of Commerce, No. 85-3982, 1987 WL 9244, at *2-3 (D.D.C. Mar. 25, 1987).',
    ],
    16 => [
      'description' => '15 U.S.C. §§ 46(f), 57b-2(f)',
      'information_withheld' => '"[A]ny trade secret or any commercial or financial information which is obtained from any person and which is privileged or confidential" and certain investigative materials received by the FTC and "provided pursuant to any compulsory process under this subchapter or which is provided voluntarily in place of such compulsory process"',
      'citation' => 'A. Michael\'s Piano, Inc. v. FTC, 18 F.3d 138, 143-44 (2d Cir. 1994) (15 U.S.C. § 57b-2); Ctr. for Digit. Democracy v. FTC, 189 F. Supp. 3d 151, 158-59 (D.D.C. 2016) (15 U.S.C. § 46(f)); Nat\'l Educ. Ass\'n v. FTC, No. 79-959-S, 1983 WL 1883, at *1 (D. Mass. Sept. 26, 1983) (15 U.S.C. § 57b-2).',
    ],
    17 => [
      'description' => '15 U.S.C. §§ 2055(a)(2), 2055(b)(1), 2055(b)(5)',
      'information_withheld' => 'Certain information reported to, or otherwise obtained by, the Consumer Product Safety Commission which either contains or relates to a trade secret or is "subject to section 552(b)(4) of Title 5"',
      'citation' => 'Consumer Prod. Safety Comm\'n v. GTE Sylvania, Inc., 447 U.S. 102, 122-23 (1980) (15 U.S.C. § 2055(b)(1)); Mulloy v. Consumer Prods. Safety Comm\'n, No. 85-3720, 1986 WL 17283, at *1 (6th Cir. July 22, 1986) (per curiam) (unpublished disposition) (15 U.S.C. § 2055(a)(2) and 15 U.S.C. § 2055(b)(5)).',
    ],
    18 => [
      'description' => '15 U.S.C. § 6801',
      'information_withheld' => 'Nonpublic personal information of customers of financial institutions',
      'citation' => 'Hodes v. HUD, 532 F. Supp. 2d 108, 117 (D.D.C. 2008).',
    ],
    19 => [
      'description' => '15 U.S.C. § 3710a(c)',
      'information_withheld' => '"[T]rade secrets or commercial or financial information that is privileged or confidential, under the meaning of section 552(b)(4) of Title 5, which is obtained in the conduct of research or as a result of activities under this chapter," which pertains to cooperative research and development agreements for the purpose of technological and industrial innovation',
      'citation' => 'Pub. Citizen Health Research Group v. NIH, 209 F. Supp. 2d 37, 43, 51 (D.D.C. 2002); DeLorme Pub. Co., Inc. v. NOAA, 917 F. Supp. 867, 871-72 (D. Me. 1996), appeal dismissed per stipulation, No. 96-1601 (1st Cir. July 8, 1996).',
    ],
    20 => [
      'description' => '15 U.S.C. § 18a(h)',
      'information_withheld' => 'Information submitted in connection with the premerger notification provisions of the Hart-Scott-Rodino Antitrust Improvements Act of 1976.',
      'citation' => 'N/A',
    ],
    21 => [
      'description' => '15 U.S.C. § 1314(g)',
      'information_withheld' => '"Any documentary material, answers to written interrogatories, or transcripts of oral testimony" provided pursuant to certain civil investigative demands pertaining to antitrust investigations',
      'citation' => 'Motion Picture Ass\'n of Am. v. DOJ, No. 80 Civ. 6612, slip op. at 1 (S.D.N.Y. Oct. 6, 1981).',
    ],
    22 => [
      'description' => '15 U.S.C. § 4305(d)',
      'information_withheld' => 'Information submitted under the National Cooperative Research and Production Act of 1993',
      'citation' => 'N/A',
    ],
    23 => [
      'description' => '16 U.S.C. § 470hh',
      'information_withheld' => 'Information pertaining to the nature and location of certain archaeological resources',
      'citation' => 'Hornbostel v. U.S. Dep\'t of the Interior, 305 F. Supp. 2d 21, 30 (D.D.C. 2003), summary affirmance granted, No. 03- 5257, 2004 WL 1900562 (D.C. Cir. Aug. 25, 2004); Starkey v. U.S. Dep\'t of the Interior, 238 F. Supp. 2d 1188, 1193-94 (S.D. Cal. 2002).',
    ],
    24 => [
      'description' => '54 U.S.C. § 100707 (formerly at 16 U.S.C. § 5937)',
      'information_withheld' => 'Information pertaining to: "endangered, threatened, rare, or commercially valuable" National Park System resources; "mineral or paleontological objects within units of the National Park System"; or "objects of cultural patrimony within units of the National Park System"',
      'citation' => 'Hornbostel v. U.S. Dep\'t of the Interior, 305 F. Supp. 2d 21, 30 (D.D.C. 2003), summary affirmance granted, No. 03- 5257, 2004 WL 1900562 (D.C. Cir. Aug. 25, 2004); Sw. Ctr. for Biological Diversity v. USDA, 170 F. Supp. 2d 931, 945 (D. Ariz. 2000), aff\'d, 314 F.3d 1060, 1062 (9th Cir. 2002); Pease v. U.S. Dep\'t of Interior, No. 1:99CV113, slip op. at 2, 4 (D. Vt. Sept. 17, 1999).',
    ],
    25 => [
      'description' => '18 U.S.C. § 701',
      'information_withheld' => 'Likeness of any badge, identification card, or other insignia, of the design prescribed by the head of any department or agency of the United States for use by any officer or employee thereof, or any colorable imitation thereof',
      'citation' => 'Jones v. IRS, No. 06-CV-322, 2008 WL 1901208, at *3-4 (W.D. Mich. Apr. 25, 2008).',
    ],
    26 => [
      'description' => '18 U.S.C. § 4208(c)',
      'information_withheld' => 'Certain records pertaining to parole determination proceedings',
      'citation' => 'DOJ v. Julian, 486 U.S. 1, 9 (1988).',
    ],
    27 => [
      'description' => '18 U.S.C. § 798',
      'information_withheld' => 'Certain classified information pertaining to the communication intelligence and cryptographic devices of the United States or any foreign government',
      'citation' => 'Larson v. Dep\'t of State, 565 F.3d 857, 868-69 (D.C. Cir. 2009); N.Y. Times Co. v. DOJ, No. 14 Civ 03776, 2016 WL 5946711, at *6 (S.D.N.Y. Aug. 18, 2016); Adejumobi v. NSA, No. 07-1237, 2007 WL 4247878, at *3 (M.D. Fla. Dec. 3, 2007), aff\'d per curiam, 287 F. App\'x 770 (11th Cir. 2008); Winter v. NSA, 569 F. Supp. 545, 546-48 (S.D. Cal. 1983).',
    ],
    28 => [
      'description' => '18 U.S.C. §§ 2510, et seq.',
      'information_withheld' => 'Wiretap requests and the contents of any wire, oral, or electronic communication obtained through wiretaps',
      'citation' => 'Payne v. DOJ, No. 96-30840, 1997 WL 450139, at *3 (5th Cir. July 11, 1997); Lam Lek Chong v. DEA, 929 F.2d 729, 732-33 (D.C. Cir. 1991).',
    ],
    29 => [
      'description' => '18 U.S.C. § 3123(d)',
      'information_withheld' => 'Certain court "order[s] authorizing or approving the installation and use of a pen register or a trap and trace device"; information pertaining to "the existence of the pen register or trap and trace device or the existence of the investigation"',
      'citation' => 'Labow v. DOJ, 831 F.3d 523, 527-28 (D.C. Cir. 2016); ACLU v. DOJ, 2014 WL 4954121, at *11 (N.D. Cal. Sept. 30, 2014); Manna v. DOJ, 815 F. Supp. 798, 812 (D.N.J. 1993), aff\'d on other grounds, 51 F.3d 1158 (3d Cir. 1995).',
    ],
    30 => [
      'description' => '18 U.S.C. § 3153',
      'information_withheld' => 'Information regarding pre-trial services',
      'citation' => 'N/A',
    ],
    31 => [
      'description' => '18 U.S.C. § 3509(d)',
      'information_withheld' => 'Certain records containing identifying information pertaining to children involved in criminal proceedings',
      'citation' => 'Corley v. DOJ, 998 F.3d 981, 982-85 (D.C. Cir. 2021).',
    ],
    32 => [
      'description' => '18 U.S.C. § 3521(b)(1)(g)',
      'information_withheld' => '"[T]he identity or location of the person relocated or protected, or any other matter concerning the person or the program after weighing [various factors]," subject to certain exceptions',
      'citation' => 'Wilson v. FBI, No. 22-3062, 2025 WL 522019, at *7 (D.D.C. Feb. 18, 2025); Bonadonna v. DOJ, 791 F. Supp. 2d 269, 270 (D. Mass. 2010), aff\'d, No. 10-1595, 2011 WL 4770189 (1st Cir. Jan. 7, 2011).',
    ],
    33 => [
      'description' => '18 U.S.C. § 5038(a)',
      'information_withheld' => 'Records of juvenile delinquency proceedings',
      'citation' => 'Lavado v. DEA, No. 90-5262, 1991 WL 119586, at *1 (D.C. Cir. June 28, 1991).',
    ],
    34 => [
      'description' => '19 U.S.C. §§ 2605(h), 2605(i)',
      'information_withheld' => 'Certain records pertaining to Cultural Property Advisory Committee proceedings where "it is determined by the President or his designee that the disclosure of matters involved in the Committee\'s proceedings would compromise the Government\'s negotiating objectives or [certain] bargaining positions"; certain "information (including trade secrets and commercial or financial information which is privileged or confidential) submitted in confidence by the private sector to officers or employees of the United States or to the [Cultural Property Advisory] Committee"',
      'citation' => 'Ancient Coin Collectors Guild v. U.S. Dep\'t of State, 641 F.3d 504, 511 (D.C. Cir. 2011) (19 U.S.C. § 2605(h)).',
    ],
    35 => [
      'description' => '19 U.S.C. § 1677f',
      'information_withheld' => 'Certain "information submitted to the administering authority or the [United States International Trade] Commission which is designated as proprietary by the person submitting the information"',
      'citation' => 'Mudge Rose Guthrie Alexander & Ferdon v. U.S. Int\'l Trade Comm\'n, 846 F.2d 1527, 1530-31 (D.C. Cir. 1988).',
    ],
    36 => [
      'description' => '21 U.S.C. § 331(j)',
      'information_withheld' => 'Certain information "concerning any method or process which as a trade secret is entitled to protection"',
      'citation' => 'Anderson v. HHS, 907 F.2d 936, 950-51 (10th Cir. 1990).',
    ],
    37 => [
      'description' => '22 U.S.C. § 1644',
      'information_withheld' => 'Records pertaining to certain claims against the German Democratic Republic',
      'citation' => 'N/A',
    ],
    38 => [
      'description' => '22 U.S.C. §§ 1461, 1461-1a',
      'information_withheld' => 'Certain program information prepared by the United States Information Agency',
      'citation' => 'Essential Info., Inc. v. USIA, 134 F.3d 1165, 1168 (D.C. Cir. 1998).',
    ],
    39 => [
      'description' => '22 U.S.C. § 2778(e)',
      'information_withheld' => 'Certain information pertaining to export license applications',
      'citation' => 'Council for a Livable World v. U.S. Dep\'t of State, 1998 U.S. Dist. LEXIS 23642 at *11 (D.D.C. Jan. 21, 1998), amended (D.D.C. Nov. 23, 1998).',
    ],
    40 => [
      'description' => '22 U.S.C. § 3104(c)',
      'information_withheld' => 'Records and reports provided pursuant to subsection (b)(2) of the section of the statute which pertains to certain "surveys and studies" regarding international investment and trade',
      'citation' => 'Young Conservative Found., Inc. v. U.S. Dep\'t of Commerce, No. 85-3982, 1987 WL 9244, at *3-4 (D.D.C. Mar. 25, 1987).',
    ],
    41 => [
      'description' => '26 U.S.C. §§ 6103, 6105',
      'information_withheld' => 'Certain tax return information, to include Taxpayer Identification Numbers of third parties, and certain tax convention information',
      'citation' => 'Church of Scientology v. IRS, 484 U.S. 9, 15 (1987) (26 U.S.C. § 6103); Elec. Priv. Info. Ctr. v. IRS, 910 F.3d 1232, 1237 (D.C. Cir. 2018); Leonard v. U.S. Dep\'t of Treasury, 590 F. App\'x. 141, 143-44 (3d Cir. 2014) (per curiam); Pac. Fisheries, Inc. v. IRS, 395 F. App\'x. 438, 440 (9th Cir. 2010) (unpublished disposition) (26 U.S.C. §§ 6103, 6105); Nosal v. IRS, 523 F. Supp. 3d 72, 79 (D.D.C. 2021).',
    ],
    42 => [
      'description' => '26 U.S.C. § 7123',
      'information_withheld' => '',
      'citation' => '',
    ],
    43 => [
      'description' => '28 U.S.C. § 652(d)',
      'information_withheld' => '"[C]onfidential dispute resolution communications"',
      'citation' => 'Yelder v. DOD, 577 F. Supp. 2d 342, 346-47 (D.D.C. 2008).',
    ],
    44 => [
      'description' => '31 U.S.C. § 5311',
      'information_withheld' => 'Records and reports on monetary instruments transactions',
      'citation' => 'N/A',
    ],
    45 => [
      'description' => '31 U.S.C. § 3730(b)(2)',
      'information_withheld' => 'Records pertaining to civil actions for false claims against the United States Government',
      'citation' => 'N/A',
    ],
    46 => [
      'description' => '31 U.S.C. § 5319',
      'information_withheld' => 'Reports pertaining to monetary instruments transactions filed under subchapter II of chapter 53 of title 31 and records of those reports',
      'citation' => 'Yunes v. DOJ, 263 F. Supp. 3d 82, 87 (D.D.C. 2017)); Council on Am.-Islamic Rels., Cal. v. FBI, 749 F. Supp. 2d 1104, 1117 (S.D. Cal. 2010); Berger v. IRS, 487 F. Supp. 2d 482, 496-97 (D.N.J. 2007), affd on other grounds, 288 F. App\'x 829 (3d Cir. 2008).',
    ],
    47 => [
      'description' => '35 U.S.C. § 122',
      'information_withheld' => 'Applications for patents; certain information pertaining to applications for patents',
      'citation' => 'Irons & Sears v. Dann, 606 F.2d 1215, 1219-21 (D.C. Cir. 1979).',
    ],
    48 => [
      'description' => '38 U.S.C. § 5705',
      'information_withheld' => 'Records created by VA as part of its medical quality-assurance program; certain other information pertaining to VA\'s medical quality-assurance program',
      'citation' => 'Schulte & Sun-Sentinel Co. v. VA, No. 86-6251, slip op. at 3-4, 12 (S.D. Fla. Feb. 2, 1996).',
    ],
    49 => [
      'description' => '38 U.S.C. § 7332',
      'information_withheld' => 'Records of patients being treated for certain conditions',
      'citation' => 'Palmer v. Derwinski, No. 91-197, slip op. at 3-4 (E.D. Ky. June 10, 1992).',
    ],
    50 => [
      'description' => '39 U.S.C. § 410(c)(2)',
      'information_withheld' => 'Commercial information which would not be disclosed under good business practice',
      'citation' => 'Bloomberg L.P. v. USPS, 118 F.4th 307, 316 (2d Cir. 2024); Wickwire Gavin, P.C. v. USPS, 356 F.3d 588, 589, 597 (4th Cir. 2004); Am. Postal Workers Union, AFL-CIO v. USPS, 742 F. Supp. 2d 76, 81-83 (D.D.C. 2010); Reid v. USPS, No. 05- 294, 2006 WL 1876682, at *5-9 (S.D. Ill. July 5, 2006); Robinett v. USPS, No. 02-1094, 2002 WL 1728582, at *5 (E.D. La. July 24, 2002).',
    ],
    51 => [
      'description' => '41 U.S.C. § 4702 (formerly at 41 U.S.C. § 253b(m)(1))',
      'information_withheld' => 'Contractor proposals that are in the possession or control of an executive agency and that have not been set forth or incorporated by reference into contracts',
      'citation' => 'Lenahan v. HHS, No. 23- 06041, 2025 WL 1159878, at *3 (N.D. Cal. Apr. 21, 2025); Sinkfield v. HUD, No. 10-885, 2012 U.S. Dist. LEXIS 35233, at *13-15 (S.D. Ohio Mar. 15, 2012); Margolin v. NASA, No. 09- 00421, 2011 WL 1303221, at *6 (D. Nev. Mar. 31, 2011); Hornbostel v. U.S. Dep\'t of the Interior, 305 F. Supp. 2d 21, 30 (D.D.C. 2003), summary affirmance granted, No. 03- 5257, 2004 WL 1900562 (D.C. Cir. Aug. 25, 2004).',
    ],
    52 => [
      'description' => '41 U.S.C. § 2102 (amending 41 U.S.C. § 423(a)(1))',
      'information_withheld' => 'Contractor bid or proposal information; source selection information',
      'citation' => 'Legal & Safety Employer Research, Inc. v. U.S. Dep\'t of the Army, No. Civ. S001748, 2001 WL 34098652, at *3-4 (E.D. Cal. May 4, 2001) (dictum).',
    ],
    53 => [
      'description' => '42 U.S.C. § 300aa-12(d)(4)(A)',
      'information_withheld' => '"Except as provided in subparagraph (B), information submitted to a special master or the court in a proceeding on a petition [for compensation under the National Vaccine Injury Compensation Program] . . . without the express written consent of the person who submitted the information"',
      'citation' => 'Long v. DOJ, 778 F. Supp. 2d 222, 234 (N.D.N.Y. 2011).',
    ],
    54 => [
      'description' => '42 U.S.C. §§ 2000e-5(b), 2000e-8(e)',
      'information_withheld' => 'Information pertaining to charges of unlawful employment practices; information obtained by the EEOC in investigating charges of unlawful employment practices',
      'citation' => 'Frito-Lay v. EEOC, 964 F. Supp. 236, 240- 43 (W.D. Ky. 1997); Am. Centennial Ins. Co. v. EEOC, 722 F. Supp. 180, 184 (D.N.J. 1989).',
    ],
    55 => [
      'description' => '42 U.S.C. § 2000g-2b',
      'information_withheld' => 'Conciliation assistance data received by the Community Relations Service',
      'citation' => 'N/A',
    ],
    56 => [
      'description' => '34 U.S.C. § 10231 (formerly at 42 U.S.C. § 3789g)',
      'information_withheld' => 'Data received by the Bureau of Justice Statistics and used only for statistical or research purposes',
      'citation' => 'N/A',
    ],
    57 => [
      'description' => '34 U.S.C § 12592 (formerly at 42 U.S.C. § 14132)',
      'information_withheld' => 'Certain "stored DNA samples and DNA analyses"',
      'citation' => 'Moore v. Nat\'l DNA Index System, 662 F. Supp. 2d 136, 140 (D.D.C. 2009).',
    ],
    58 => [
      'description' => '42 U.S.C. § 405(r)',
      'information_withheld' => 'Death certificates and records pertaining to deaths provided to the Commissioner of Social Security under this subsection',
      'citation' => 'Int\'l Diatomite Producers Ass\'n v. SSA, No. 92-1634, 1993 WL 137286, at *3 (N.D. Cal. Apr. 28, 1993), appeal dismissed per stipulation, No. 93-16204 (9th Cir. Oct. 27, 1993).',
    ],
    59 => [
      'description' => '42 U.S.C. § 2162, 2167, 2168(a)(1)',
      'information_withheld' => '"Restricted Data" pertaining to atomic weapons and special nuclear material and certain safeguards information for nuclear material',
      'citation' => 'Meeropol v. Meese, 790 F.2d 942 (D.C. Cir. 1986).',
    ],
    60 => [
      'description' => '42 U.S.C. §§ 2286d(b), 2286d(h)(3)',
      'information_withheld' => 'Defense Nuclear Facilities Safety Board recommendations',
      'citation' => 'Nat. Res. Def. Council, Inc. v. Def. Nuclear Facilities Safety Bd., 969 F.2d 1248, 1249, 1251 (D.C. Cir. 1992).',
    ],
    61 => [
      'description' => '42 U.S.C. § 3610(d)',
      'information_withheld' => 'Records pertaining to conciliation of disputes regarding allegations of unfair housing practices; information derived from housing discrimination investigations and any final investigative reports pertaining to such investigations',
      'citation' => 'West v. Jackson, 448 F. Supp. 2d 207, 212-13 (D.D.C. 2006), summary affirmance granted & motion to remand denied, No. 06-5281, 2007 WL 1723362 (D.C. Cir. Mar. 6, 2007).',
    ],
    62 => [
      'description' => '45 U.S.C. § 362(d)',
      'information_withheld' => 'Information obtained by the Railroad Retirement Board that reveals, in any manner, an employee\'s identity',
      'citation' => 'Ass\'n of Retired R.R. Workers v. U.S. R.R. Retirement Bd., 830 F.2d 331, 334 (D.C. Cir. 1987); Nat\'l Ass\'n of Retired & Veteran Ry. Employees v. R.R. Ret. Bd., No. 87-117, slip op. at 5 (N.D. Ohio Feb. 20, 1991).',
    ],
    63 => [
      'description' => '47 U.S.C. § 605',
      'information_withheld' => 'Certain information pertaining to interstate or foreign communication by wire or radio',
      'citation' => 'Reston v. FCC, 492 F. Supp. 697, 699-700 (D.D.C. 1980).',
    ],
    64 => [
      'description' => '49 U.S.C. § 114(r) (formerly at 49 U.S.C. § 114(s))',
      'information_withheld' => 'Information obtained or developed in carrying out security under the authority of the Aviation and Transportation Security Act or under chapter 449 of this title',
      'citation' => 'Magassa v. TSA, No. 19-01953, 2022 WL 971207, at *5 (D.D.C Mar. 31, 2022); Skurow v. DHS, 892 F. Supp. 2d 319, 329-30 (D.D.C. 2012); Gordon v. FBI, 390 F. Supp. 2d 897, 900 (N.D. Cal. 2004).',
    ],
    65 => [
      'description' => '49 U.S.C. § 1114(c)',
      'information_withheld' => 'Certain "cockpit voice or video recorder recording[s] or transcript[s] of communications by and between flight crew members and ground stations pertaining to [] incident[s] investigated by the [National Transportation Safety] Board"',
      'citation' => 'McGilvra v. NTSB, 840 F. Supp. 100, 102 (D. Colo. 1993).',
    ],
    66 => [
      'description' => '[Statute repealed, cite to ID 64]',
      'information_withheld' => '',
      'citation' => '',
    ],
    67 => [
      'description' => '50 U.S.C. § 3605 (formerly at 50 U.S.C. § 402 note)',
      'information_withheld' => 'Information pertaining to the functions or organization of NSA; certain information pertaining to NSA employees',
      'citation' => 'ACLU v. DOJ, 681 F.3d 61, 72-75 (2d Cir. 2012); Elec. Priv. Info. Ctr. v. NSA, 678 F.3d 926, 931 (D.C. Cir. 2012); Houghton v. NSA, 378 F. App\'x 235, 238-39 (3d Cir. 2010) (per curiam); Lahr v. NTSB, 569 F.3d 964, 985 (9th Cir. 2009).',
    ],
    68 => [
      'description' => '50 U.S.C. § 3507 (formerly at 50 U.S.C. § 403g)',
      'information_withheld' => 'Intelligence sources and methods; certain information pertaining to Agency employees, specifically: "the organization, functions, names, official titles, salaries, or numbers of personnel employed by the Agency"',
      'citation' => 'Assassination Archives & Rsch. Ctr. v. CIA, No. 18-5280, 2019 WL 691517 (D.C. Cir. Feb. 15, 2019) (per curiam); ACLU v. DOJ, 681 F.3d 61, 72-75 (2d Cir. 2012); Berman v. CIA, 501 F.3d 1136, 1137-38, 1140 (9th Cir. 2007); Makky v. Chertoff, 489 F. Supp. 2d 421, 442 (D.N.J. 2007), aff\'d on other grounds, 541 F. 3d 205 (3d Cir. 2008).',
    ],
    69 => [
      'description' => '50 U.S.C. § 3024(h)(1) (formerly at 50 U.S.C. § 403-1(i)(1))',
      'information_withheld' => 'Intelligence sources and methods',
      'citation' => 'CIA v. Sims, 471 U.S. 159, 167 (1985).',
    ],
    70 => [
      'description' => '50 U.S.C. § 3141(a) (formerly at 50 U.S.C. § 431)',
      'information_withheld' => '[O]perational files of the Central Intelligence Agency',
      'citation' => 'CIA v. Sims, 471 U.S. 159, 167, 174 n.19 (1985) (dictum); Wolf v. CIA, 569 F. Supp. 2d 1, 8 (D.D.C. 2008).',
    ],
    71 => [
      'description' => '50 U.S.C. § 3143 (formerly at 50 U.S.C. § 432a)',
      'information_withheld' => 'Operational files of the National Reconnaissance Office',
      'citation' => 'Aftergood v. Nat\'l Reconnaissance Off., 441 F. Supp. 2d 37, 46 (D.D.C. 2006).',
    ],
    72 => [
      'description' => '50 U.S.C. § 1702(a)(1)',
      'information_withheld' => 'Authorizes the President to "regulate . . . prevent or prohibit . . . importation or exportation of . . . any property in which any foreign country or a national thereof has any interest . . . by any person, or with respect to any property, subject to the jurisdiction of the United States"',
      'citation' => 'Wis. Project on Nuclear Arms Control v. U.S. Dep\'t of Com., 317 F.3d 275, 284 (D.C. Cir. 2003).',
    ],
    73 => [
      'description' => '50 U.S.C. § 4820(h)(1) (formerly at 50 U.S.C. § 4614(c)(1))',
      'information_withheld' => 'Information pertaining to license applications under the Export Administration Act',
      'citation' => 'Wis. Project on Nuclear Arms Control v. U.S. Dep\'t of Commerce, 317 F.3d 275, 284 (D.C. Cir. 2003); Times Publ\'g Co. v. U.S. Dep\'t of Commerce, 236 F.3d 1286, 1292 (11th Cir. 2001); Lessner v. U.S. Dep\'t of Commerce, 827 F.2d 1333, 1336-37 (9th Cir. 1987).',
    ],
    74 => [
      'description' => 'Fed. R. Crim. P. 6(e), enacted by Act of July 30, 1977, Pub. L. No. 95-78, § 2, 91 Stat. 319',
      'information_withheld' => 'Certain records that would reveal some secret aspect pertaining to grand jury proceedings',
      'citation' => 'Kalbers v. DOJ, 166 F.4th 783, 795-96 (9th Cir. 2026); Sorin v. DOJ, No. 18-99, 2018 WL 6431027 (2d Cir. Dec. 6, 2018) (per curium); Sussman v. USMS, 494 F.3d 1106, 1113 (D.C. Cir. 2007); Widi v. McNeil, No. 12-00188, 2016 WL 4394724, at *23 (D. Me. Aug 16, 2016); Durham v. U.S. Att\'y Gen., No. 06-843, 2008 WL 620744, at *2 (E.D. Tex. Mar. 3, 2008); Cozen O\'Connor v. U.S. Dep\'t of Treasury, 570 F. Supp. 2d 749, 776 (E.D. Pa. 2008).',
    ],
    75 => [
      'description' => '[Do not use]',
      'information_withheld' => '',
      'citation' => '',
    ],
    76 => [
      'description' => 'Pub. L. No. 111- 8, 123 Stat. 524',
      'information_withheld' => 'Information required to be maintained by Federal Firearms Licensees and information maintained in the ATF Firearms Tracing Systems',
      'citation' => 'N/A',
    ],
    77 => [
      'description' => '[Statute not listed]',
      'information_withheld' => '',
      'citation' => '',
    ],
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
            $description = $id === 77 ? trim($columns[5]) : self::STATUTES[$id]['description'];
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
            // Only code 77 uses request-supplied withheld text and citations.
            $withheld = $id === 77 ? trim($columns[6]) : self::STATUTES[$id]['information_withheld'];
            if ($withheld !== '') {
              $statutes[$key]['information_withheld'][$withheld] = $withheld;
            }
            $citation = $id === 77 ? trim($columns[7]) : self::STATUTES[$id]['citation'];
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
