# FOIA Raw Data to Report

Adds a **Generate XML Report** submit button to full/default displays of
`raw_data_to_report` nodes.  Access requires node view and update access,
plus either the agency_manager role with a matching nonempty agency or
the bypass node access permission.

The button calls `GenerateXmlReportForm::generateXmlReport()`.

Note tests in RawDataToReport.feature.

## Queue processing

The button adds `['nid' => (int) $node->id()]` to the
`raw_data_to_report_processing` queue, titled **Raw Data to Report Processing**.
The web request only queues the work. No CSV data is read into the queue item.

Run from the project root on the server:

```bash
drush queue:run raw_data_to_report_processing
```

The worker deliberately has no `cron` annotation setting. Drupal's automatic
cron and `drush cron` will not process it. Schedule the explicit `queue:run`
command using server cron; this module does not install a server schedule.
For example, a bounded invocation for that schedule is:

```bash
drush queue:run raw_data_to_report_processing --items-limit=1 --lease-time=3600
```

Use a lease longer than the maximum processing duration and avoid overlapping
runners. Revisit the lease and server PHP resource limits when CSV conversion
is implemented; a lease is not a processing timeout.

`RawDataToReportProcessing::generateXmlReport()` uses `XmlReportBuilder` to
create a **partial annual report XML document** and attaches it to `field_request_data_xml`.
The document uses the example's `iepd:FoiaAnnualReport` root and namespaces,
with `nc:DocumentApplicationName` set to `FOIA Annual Report Workbook`
(application version `1.1`), `nc:DocumentCreationDate/nc:Date` set to the current
generation date (`YYYY-MM-DD`, Drupal runtime timezone), and
`nc:DocumentDescriptionText` set to `FOIA Annual Report`. It is well-formed XML,
but is not yet a complete, schema-valid annual report. Additional CSV-derived sections
will be added to this module's builder; `foia_export_xml` is not modified.
The `nc:Organization` section uses the linked Agency taxonomy term's name and
`field_agency_abbreviation`, with `s:id="ORG0"`. Its `nc:OrganizationSubUnit`
children use the components from the uploaded paragraphs, in paragraph order,
with IDs `ORG1`, `ORG2`, etc. Component abbreviations come from
`field_agency_comp_abbreviation` on each component. Names and abbreviations are
escaped as XML text. The report node's own abbreviation is not used.
The Organization section is followed by `foia:DocumentFiscalYearDate`, using
the report node's `field_foia_annual_report_yr`, also used for CSV validation.

Each queue run compares attached CSV components with all `agency_component`
nodes linked to the Agency (including unpublished components). It appends
`Warning: no CSV has been attached for these Components: ` followed by missing
component names. This warning does not block generation; existing validation
errors still do. Components with an attached but invalid CSV receive validation
errors rather than a missing-upload warning. Missing components are not added
as XML subunits. Previous warnings are cleared with other processing messages.

It uses the field's configured directory and storage
scheme, with a unique filename, and replaces the current field reference.
Each filename includes the generation timestamp in `YYYY-MM-DD-HH-MM-SS`
format using the Drupal runtime timezone (normally the site default). Drupal
adds a numeric suffix if that filename already exists.
After the replacement is attached and the node saves successfully, the previous
XML file entity and its physical file are deleted. Previous revisions referencing
that file will no longer have that download available. Drupal file field hooks
manage file permanence and usage on node save.

Each click creates a separate job. The worker reads the latest node state when
processing; it does not snapshot the CSV selection. Deleted nodes are skipped.
Processing exceptions leave the item available for retry after its lease expires.
All CSVs are validated before aggregation and XML generation. Statute usage,
processed request statistics, dispositions, other denial reasons, and applied
exemptions, appeal processing statistics, and appeal dispositions are aggregated; the remaining
sections are not implemented yet.

## CSV validation and messages

Import configuration before running the updated worker. It requires the new
`field_messages` field on `raw_data_to_report` (label **Messages**, formatted
long text with summary, like Body). Messages use the plain text format, appear
on the node view, and are hidden from the edit form.

Each processing attempt clears previous messages and checks every component
upload. `CsvValidator::validate()`
streams the CSV one record at a time and checks for 29 columns, matching
`OIP Request Raw Data (FOIA Star submission) (final).csv`. Both the header and
data records are checked. Blank records are skipped; quoted commas, escaped
quotes, and multiline values are supported. Record numbers include the header
and blank records and are not physical line numbers for multiline CSVs.

The first validation error produces a human-readable message identifying the
CSV record. Column-count errors include the expected and actual counts. Missing, unreadable, empty, and non-CSV uploads also
produce messages. Validation failures finish the queue item without generating
XML or changing any existing XML attachment. Correct the CSV and click Generate
XML Report again to retry. Each upload result identifies its component and filename,
with **CSV validated.** for files that pass. Any failure prevents generation of
the single replacement XML; all files are still checked.

Column checks are grouped in `CsvValidator::validate()` in column order, with
comments describing each rule. The first nonblank record is treated as the
header; its names are not validated. Data records currently require:

- **A (Component):** a nonblank value.
- **B (Request Number):** a nonblank value, unique within that CSV. Comparisons
  are case-sensitive, ignore surrounding whitespace, and preserve leading zeroes.
- **C (Is This a Consultation):** uppercase `Y` or `N`, ignoring surrounding
  whitespace. For `Y`, all columns except A, B, C, I, and K must be blank.
  K may be empty; I is required and validated as a date below.
- **D (Days Allowed):** `20` or `30`, ignoring surrounding whitespace, unless
  C is `Y`. Consultation rows must leave D blank under the Column C rule.
- **E (Exemption 3 Statutes):** optional comma-separated integer IDs from 1
  through 77. Whitespace around IDs is allowed; empty entries are invalid.
  If any ID is 77, F, G, and H must each contain information. Otherwise, all
  three must be blank. A nonblank E also requires a standalone `3` in P's
  comma-separated exemptions. Consultation rows must leave E blank.
- **F (Other Exemption 3 Statutes):** when nonblank, requires code 77 in E.
- **G (Information Withheld):** when nonblank, requires code 77 in E and data in F.
- **H (Case Citation):** when nonblank, requires code 77 in E and data in F and G.
- **I (Date Initially Received):** required, including for consultations. Must
  be a valid calendar date in `MM/DD/YYYY` order (single-digit months and days
  are also accepted), on or before September 30 of the report node's Year.
  Earlier years are allowed; surrounding whitespace is ignored.
- **J (Date Perfected):** optional, with the same date format as I. If provided,
  must be on or after I and on or before September 30 of the report node's Year,
  and M must contain uppercase `S`, `C`, or `E`. Consultation rows must leave
  J blank under the Column C rule.
- **K (Date Completed):** optional unless N contains a disposition. If provided,
  must be a valid date in the same format as I and J, between October 1 of the
  previous year and September 30 of the report node's Year, inclusive. Must not
  precede J when J is provided; equality is allowed. Applies to consultations too.
- **L (Days Tolled):** optional non-negative integer, requiring J when populated.
  If K is populated, cannot exceed working days after J through K (J excluded,
  K included). Same-day completion allows zero. Weekends and the supplied
  holidays are excluded. With K blank, only the integer and J requirements apply.
- **M (Track):** may be blank unless J is populated, in which case uppercase
  `S`, `C`, or `E` is required by the Column J check. Independently, uppercase
  `G` in S requires uppercase `E` in M, even if J is blank. Surrounding
  whitespace is ignored.
- **N (Disposition Reason):** required when K is populated and C is `N`.
  Code `12` requires data in O. Codes `1`, `2`, `3`, `4`, `5`, and `7` require
  J to be populated; codes `8` and `9` require J to be blank. Codes are matched
  exactly after trimming surrounding whitespace. The Column K check still
  requires a completed date whenever N is populated.
- **O (Disposition "Other" Reason):** when nonblank, requires code `12` in N.
  This complements the Column N rule requiring O when N is `12`.
- **P (Disposition Exemption(s) Applied):** optional comma-separated
  alphanumeric entries, allowing whitespace around entries. Required for N =
  `3`; nonblank P requires N = `2` or `3`. A standalone exemption `3` requires
  information in E, complementing the existing E-to-P requirement.
- **Q (Request for Expedited Processing - Date Received):** optional date in
  the same format as I, J, and K. Must be on or before September 30 of the
  report node's Year; earlier years are allowed.
- **R (Request for Expedited Processing - Date of Determination):** optional
  unless Q, S, or T is populated. Uses the same date format as Q and must fall
  between October 1 of the previous year and September 30 of the report node's
  Year, inclusive. Cannot precede Q when Q is populated; equality is allowed.
- **S (Request for Expedited Processing - Granted/Denied):** when R is populated,
  requires uppercase `G` or `D`, ignoring surrounding whitespace.
- **T (Request for Fee Waiver - Date Adjudication Began):** optional date in
  the same format as Q. Must be on or before September 30 of the report node's
  Year; earlier years are allowed. The existing R rule still requires R when
  T is populated.
- **U (Request for Fee Waiver - Date Adjudication Completed):** optional date in
  the same format as T. Must fall between October 1 of the previous year and
  September 30 of the report node's Year, inclusive. Cannot precede T when T
  is populated; equality is allowed.
- **V (Request for Fee Waiver - Granted/Denied):** may be blank unless U has a
  date, in which case uppercase `G` or `D` is required. Surrounding whitespace
  is ignored.

`WorkingDays::FEDERAL_HOLIDAYS` embeds all 205 dates supplied in
`federal-holidays.txt`, covering 2008–2026. The file is not read at runtime.
Maintain the constant for future years; dates outside its coverage currently
exclude weekends only. Working-day arithmetic counts whole weeks and a short
remainder rather than looping over every calendar day for each CSV record.

The queue worker passes `field_foia_annual_report_yr` to the validator as its
required second argument. A missing or invalid Year prevents CSV validation
and XML generation, with a message asking for a Year between 1 and 9999.

Checks run in column order, so earlier Column C or E errors take precedence
when a row also violates the F, G, or H rules.

Whitespace-only cells count as blank. Request numbers are kept in a lookup set
while streaming; memory usage grows with the number of distinct request numbers,
without retaining entire records. Duplicate tracking resets for each CSV upload.
Future checks belong in the same method. The reference CSV is not needed at
runtime; its 29-column count is recorded in `EXPECTED_COLUMNS`.

Run validator unit tests with:

```bash
ddev exec vendor/bin/phpunit -c docroot/core/phpunit.xml.dist docroot/modules/custom/foia_raw_data_to_report/tests/src/Unit/CsvValidatorTest.php
```

## Access and private files

Anonymous users cannot view `raw_data_to_report` nodes. Authenticated users
remain subject to existing node and field permissions. CSV paragraph fields and
the report XML field use
`private://`, so direct downloads go through Drupal's file access checks.
The XML worker inherits this scheme from the field configuration. A scoped
file-access hook enforces the report node and field permissions for attached
CSV/XML files even when File Entity overrides core's file access handler. For
paragraph uploads, it follows the parent report and checks that the paragraph
revision is actually attached. Detached or historical paragraph uploads cannot
gain download access through the current report. Before a paragraph is saved,
the authenticated uploader can access their own temporary CSV in the dedicated
`private://request_data_tool/components/` directory so the upload widget works.

Before importing this configuration on a server, configure
`$settings['file_private_path']` to a persistent writable directory outside the
web root. The local DDEV default is `files-private/` at the repository root.
Rebuild caches after changing the private path. These changes apply to new
uploads; no existing files are migrated.

## Component CSV uploads

The report remains unique per Agency and keeps its existing Year and single XML
field. `field_component_uploads` is an unlimited Paragraphs reference to
`raw_data_component_upload`. Each paragraph contains:

- Required `field_agency_component`, reusing the existing paragraph field storage.
- Required, single-value `field_request_data_csv`, accepting CSV files only and
  storing them privately.

A subset of the agency's components is allowed. At least one upload is needed
for processing; reports can otherwise be saved without uploads. Duplicate
components and components belonging to another agency are rejected by entity
validation and checked again by the queue worker. Missing components/files also
produce processing messages.

The component autocomplete is limited to the parent report's agency and refreshes
when Agency changes. Existing component-selection views depend on the report
node URL, so this bundle uses a dedicated selection handler supporting unsaved
reports. The shared component field storage and other paragraph bundles are not
changed.

Import the exported configuration and rebuild caches before running the queue.
The old node-level CSV field is replaced, with no migration of existing uploads.
This follows the pre-launch assumption that existing upload content is disposable.
The queue payload remains the parent node ID. Edits during processing are not
locked or snapshotted. CSV conversion produces one partial annual report XML document
only after every component upload passes validation.

Local integration verification (creates and removes temporary fixtures):

```bash
ddev drush php:script docroot/modules/custom/foia_raw_data_to_report/tests/integration/component_uploads.php
```

Browser and regression verification:

```bash
ddev behat -f RawDataToReport.feature
```

## Exemption 3 statute aggregation

After validation, `StatuteAggregator` streams each component CSV in a separate
pass, one record at a time. Component identity comes from the paragraph, not
Column A. Each distinct statute ID in Column E counts once per request row;
agency totals sum the component counts. No request rows are retained.

`StatuteAggregator::STATUTES` embeds all 77 labels from
`ID_Statute_exemption_3.txt` verbatim, including its placeholder labels and
punctuation. That file is not needed at runtime. Code 77 uses the trimmed
Other Statute description in F; different descriptions produce separate statute
entries. Other codes are grouped by their numeric ID.

Distinct, nonblank Information Withheld values from G and Case Citation values
from H are retained separately per statute and joined with newlines. G populates
`foia:ReliedUponStatuteInformationWithheldText`; H populates
`nc:Case/nc:CaseTitleText`. Memory grows with distinct statutes, components, and
text values, rather than total CSV rows; retaining all unique text can still
consume memory if every request has different text.

`XmlReportBuilder` receives summaries, not CSV files. It adds
`foia:Exemption3StatuteSection` after `foia:DocumentFiscalYearDate`, with `ES1`,
`ES2`, etc. definitions followed by component usage associations and an `ORG0`
agency total for each statute. Only components with nonzero usage get an
association. No statutes produces an empty section. Names and citation text
are escaped as XML text. Read failures abort generation before an existing XML
file is replaced. The existing `foia_export_xml` module is unchanged.

## Processed request statistics

`RequestStatisticsAggregator` makes a separate streaming pass after validation,
retaining only four counters per component. Every data row counts, including
consultations and rows without statute codes. Headers and blank records are
skipped using the same rules as validation.

- Pending at start: I is before October 1 of the previous year.
- Received: I is within the fiscal year, including both boundaries.
- Processed: K is within the fiscal year, including both boundaries.
- Pending at end: K is blank (including whitespace-only cells).

Agency totals sum each counter across components. Both component and overall
counts must satisfy `pending start + received - processed = pending end`.
A mismatch or read failure raises a processing exception before the existing
XML is replaced. Normal CSV validation already enforces the date constraints
that make this equation hold. Header-only uploads produce four zero counts.

The builder adds `foia:ProcessedRequestSection` after the statute section.
Each uploaded component gets `PS1`, `PS2`, etc., and the agency total gets
`PS0`. All four quantities are emitted, including zeroes. Corresponding
`foia:ProcessingStatisticsOrganizationAssociation` elements reference those
statistics using `foia:ComponentDataReference` and link to `ORG1`, `ORG2`, etc.
or agency `ORG0` using `nc:OrganizationReference`.

## Request disposition statistics

`DispositionAggregator` streams each validated CSV in a separate pass, keeping
12 counters per component and summing them for the agency overall. Each data
row with a nonblank Column N counts once for that code. Blank dispositions,
headers, and blank records do not contribute. Date eligibility is enforced by
existing CSV validation (a disposition requires a completion date in the report
fiscal year). Header-only components retain all twelve zero counts.

`DispositionAggregator::DISPOSITIONS` embeds the twelve labels from
`ID_Disposition.txt`; the file is not needed at runtime. Codes 1–3 map to the
FullGrant, PartialGrant, and FullExemptionDenial quantity elements. Codes 4–12
map to `NonExemptionDenial` entries using the XML reason codes from the example
and existing exporter: `NoRecords`, `Referred`, `Withdrawn`, `FeeRelated`,
`NotDescribed`, `ImproperRequest`, `NotAgency`, `Duplicate`, and `Other`.
These XML codes differ from the mapping file's human-readable labels.

`foia:RequestDispositionSection` follows the processed request section. Each
component receives `RD1`, `RD2`, etc., and the overall agency receives `RD0`.
All twelve quantities, including zeroes, and their `RequestDispositionTotalQuantity`
sum are output. `RequestDispositionOrganizationAssociation` links each RD ID
through `ComponentDataReference` to its corresponding ORG ID through
`OrganizationReference`. Unknown nonblank codes or read failures raise a
processing exception before any existing XML is replaced.

## Other denial reasons

`OtherDenialReasonAggregator` streams Column O, counting each nonblank value
once per request. It trims surrounding whitespace but preserves case,
punctuation, and internal whitespace, including multiline text. Numeric-looking
reasons remain text. Distinct reasons are sorted by text for stable output.
Agency counts sum each reason's usage across components. Memory grows with the
number and length of distinct reasons, not with repeated request rows.

`foia:RequestDenialOtherReasonSection` follows the disposition section. Each
component has a `ComponentOtherDenialReason` (`CODR1`, `CODR2`, etc.) containing
reason descriptions and usage counts. The agency entry (`CODR0`) includes the
summed count for every distinct reason. Each entry has a
`ComponentOtherDenialReasonQuantity` equal to the sum of its usage counts,
matching the example (two reasons used 1 and 3 times give a total of 4).
Components with no reasons have zero totals and no reason entries.
`OtherDenialReasonOrganizationAssociation` links each CODR entry to its ORG
organization. Text is escaped safely when written to XML. Blank records and
headers are skipped; read failures abort generation before XML replacement.

## Applied exemptions

`AppliedExemptionsAggregator` streams Column P in a separate pass, splits each
nonblank cell on commas, trims each code, and normalizes letters to uppercase.
Each distinct exemption counts once per request, so `5,7a,7A` increments 5 and
7(A) once each. Blank cells, headers, and blank records do not contribute.
Fourteen counters per component cover 1–6, 7(A)–7(F), 8, and 9; agency counters
sum the component values. Unknown codes raise a processing exception before
existing XML is replaced rather than being silently omitted.

`foia:RequestDispositionAppliedExemptionsSection` follows the Other denial
reason section. Each `ComponentAppliedExemptions` has `RDE1`, `RDE2`, etc., or
agency `RDE0`. `AppliedExemption` elements contain the example's labels such
as `Ex. 5` and `Ex. 7(A)` plus `AppliedExemptionQuantity`. Zero-count exemptions
are omitted; components with no exemptions retain their empty container and
organization association.
`ComponentAppliedExemptionsOrganizationAssociation` links each RDE entry to
its corresponding ORG entry. No total across exemptions is emitted, because a
single request may use several exemptions. Memory retains only counters, not
request rows. Existing CSV validation continues to run before aggregation.

## Processed appeal statistics

`AppealStatisticsAggregator` streams X (Appeal Date Received) and Y (Appeal
Date Closed), retaining four counters per component and summing agency totals.
Rows with both dates blank do not contribute. Dates use the existing
month/day/four-digit-year format, including single-digit months and days.

- Pending at start: X precedes October 1 of the previous year.
- Received: X falls within the fiscal year, including both boundaries.
- Processed: Y falls within the fiscal year, including both boundaries.
- Pending at end: X is populated and Y is blank.

Both component and agency counts must satisfy
`pending start + received - processed = pending end`. Because X and Y do not
have separate CSV validation rules yet, the accumulator rejects invalid dates,
Y without X, Y preceding X, and X after year-end. A populated Y outside the
fiscal year makes these counters fail the balance check; such rows must be
reviewed rather than silently omitted or reported with adjusted totals.
Errors raise processing exceptions before any existing XML is replaced.

`foia:ProcessedAppealSection` follows the applied exemptions section. It uses
the same four `ProcessingStatistics` quantities as requests, with IDs `PA1`,
`PA2`, etc. and agency `PA0`. `ProcessingStatisticsOrganizationAssociation`
links each PA ID to its corresponding ORG ID. Components with no appeals have
four zero quantities. Request statistics continue to use distinct PS IDs.

## Appeal dispositions

`AppealDispositionAggregator` streams Column Z and counts the four specified
outcomes: `Affirmed on Appeal`, `Partially Affirmed & Partially Reversed/Remanded`,
`Completely Reversed/Remanded`, and `Closed for Other Reasons`. Matching is exact
and case-sensitive after trimming surrounding whitespace. Blank cells, headers,
and blank records do not contribute. Each populated row contributes once;
agency counters sum the component counters. No additional date filter is applied
by this accumulator. Existing validation and appeal date checks still run first.
Unrecognized nonblank outcomes or read failures abort generation before the
previous XML is replaced.

`foia:AppealDispositionSection` follows the processed appeal section. It emits
Affirmed, Partial, Reversed, and Other quantities and their
`AppealDispositionTotalQuantity` sum, including zeroes for components without
outcomes. IDs `AD1`, `AD2`, etc. and agency `AD0` link to their corresponding ORG
IDs through `AppealDispositionOrganizationAssociation`. Only four counters per
component are retained in memory.

## Appeal applied exemptions

The queue reuses `AppliedExemptionsAggregator` with zero-based column 28 (AC)
for appeal exemptions, independently of Column P's request counts. It splits
commas, trims surrounding whitespace, normalizes letter case, and counts each
exemption once per row. For example, `5,7a,7A` contributes one count each to
5 and 7(A). Blank cells, headers, and blank records are skipped. Unknown codes
(including malformed separators or empty list entries) stop generation before
an existing XML attachment is replaced.

`foia:AppealDispositionAppliedExemptionsSection` follows the appeal disposition
section. `ComponentAppliedExemptions` IDs `ADE1`, `ADE2`, etc. and agency `ADE0`
contain nonzero `AppliedExemption` entries with labels such as `Ex. 5` and
`Ex. 7(A)` and their quantities. Agency counts sum the component counts.
`ComponentAppliedExemptionsOrganizationAssociation` links each ADE entry to
its corresponding ORG entry. Empty components retain their container and
association, but no zero-count exemption entries. No combined total is emitted.
Request exemptions continue to use a separate section and RDE IDs.

## Appeal non-exemption denials

`AppealNonExemptionDenialAggregator` streams Column AA and maps the 11 CSV
reason labels to XML codes using its `REASONS` constant. Comma-separated
reasons are trimmed and counted once per distinct reason per row. Blank
cells are ignored; unknown labels stop generation with the component and CSV
record in the error message. Agency counts sum all component counts.

`AppealNonExemptionDenialSection` follows the appeal exemptions section.
Each component and the agency have an `AppealNonExemptionDenial` entry with
all 11 reason counts, including zero counts, matching the example XML.
`ANE1`, `ANE2`, etc. reference the component organizations; `ANE0` references
`ORG0` for the agency. No combined total across reasons is emitted.

## Appeal other denial reasons

`OtherDenialReasonAggregator` also streams Column AB (column index 27). Unlike
Column O, appeal reasons are split on commas. Surrounding whitespace and empty
entries are ignored; each distinct reason counts once per row. Text is retained
without a code mapping, preserving case and internal whitespace. Agency counts
sum the component counts, and reasons are sorted by text for stable output.

`AppealDenialOtherReasonSection` follows the appeal non-exemption denial section.
It includes reason descriptions and counts for each component and the agency,
plus `ComponentOtherDenialReasonQuantity` as the sum of usage counts. `ADOR1`,
`ADOR2`, etc. link to component organizations; `ADOR0` links to `ORG0`. Empty
components retain a zero total and an organization association.

## Appeal response times

`AppealResponseTimeAggregator` uses Columns X and Y. Rows without an Appeal
Date Received are excluded; a closed date without a received date is rejected.
The start is the later of Appeal Date Received and October 1 of the previous
year. The end is Appeal Date Closed, or September 30 of the report year when
Y is blank.
Elapsed calendar days exclude the starting day and include the ending day;
same-day completion is zero. Weekends and holidays count.

Day-frequency maps keep memory independent of the number of rows. They yield
the exact median (averaging the middle pair for even counts), average rounded
to two decimals, minimum, and maximum. The agency uses combined frequencies,
not averages of component statistics. Empty components report zeros.

`AppealResponseTimeSection` follows the appeal other-reasons section, using
`ART1`, `ART2`, etc. for components and `ART0` for the agency, associated with
their Organization entries. XML averages always display two decimal places.

## Oldest pending appeals

`OldestPendingAppealAggregator` streams rows with a nonblank Column X and a
blank Column Y. It retains only the ten earliest received dates per component
and the ten earliest across the agency. Repeated dates remain separate items;
fewer than ten pending appeals produce only the items available.

Pending days are elapsed calendar days from the actual received date through
September 30 of the report year, without clamping to the fiscal-year start.
Same-day receipt is zero days. XML receipt dates use `YYYY-MM-DD`.
`OldestPendingAppealSection` follows the appeal response times, with `OPA1`,
`OPA2`, etc. linked to components and `OPA0` to the agency. Components without
pending appeals retain an empty container and their organization association.

## Processed request response times

`ProcessedResponseTimeAggregator` uses completed rows (K) with S, C, or E in M.
The start is J when present, otherwise I. No fiscal-year clamping or Days Tolled
subtraction is applied. A counted row without either start date, or with an end
before its start, stops generation with a contextual error.

`WorkingDays` shares the existing federal holiday constant and calculation with
CSV validation: exclude the start day, include the end day, skip weekends and
listed holidays. The calendar currently covers 2008–2026 and must be maintained.
Day-frequency maps provide exact medians, averages, minima and maxima without
retaining individual rows. Agency statistics use combined frequencies per track.

`ProcessedResponseTimeSection` follows oldest pending appeals. `PRT1`, `PRT2`,
etc. reference component organizations; `PRT0` references the agency. Each has
Simple, Complex and Expedited response-time elements, empty for unused tracks.
Statistics below one use the corresponding `DaysCode` element with `LT1`;
otherwise `DaysValue` is used, with averages formatted to two decimal places.
The less-than-one comparison occurs before average rounding.

Report-generation exceptions are appended after validation messages in the
node's Messages field as plain text. The worker reloads the stored node before
saving the message so unsaved file-field changes are not persisted accidentally.
The exception is rethrown to preserve Drush logging and queue retry behavior.
Each new attempt clears previous messages as before.

## Information granted response times

`InformationGrantedResponseTimeSection` uses the same accumulator and XML writer
as processed response times, with `information_granted_only` enabled to select
Column N codes 1 and 2. Completed dates, track selection, working days, empty
tracks, agency weighting, two-decimal averages, and `LT1` handling are unchanged.
The section follows processed response times and uses `IGRT1`, `IGRT2`, etc.
for components and `IGRT0` for the agency. Associations retain the example's
`ProcessedResponseTimeOrganizationAssociation` element name.

## Simple response-time increments

`aggregateSimpleIncrements()` shares the processed-response CSV reader and
working-day calculation. It includes completed requests with Track S and
Disposition 1 or 2. Zero-day completions count in 1–20. The thirteen bins
include both endpoints, followed by the open-ended 401+ bin. Agency counts
sum component counts.

`SimpleResponseTimeIncrementsSection` follows information-granted response
times. Every component and the agency have all thirteen `TimeIncrement`
entries (including zero counts), followed by `TimeIncrementTotalQuantity`.
`SRT1`, `SRT2`, etc. refer to components and `SRT0` refers to the agency.

## Complex response-time increments

`aggregateComplexIncrements()` uses the same thirteen bins and working-day
calculation as simple increments, selecting Track C instead of S. Completed
requests with disposition 1 or 2 contribute; zero-day responses count in 1–20.
All bins, including zero counts, and their sum are emitted for every component
and the agency. `ComplexResponseTimeIncrementsSection` follows the simple
section and uses `CRT1`, `CRT2`, etc. and agency `CRT0` organization references.

## Expedited response-time increments

`aggregateExpeditedIncrements()` selects Track E, using the same calculation,
disposition 1/2 filter, thirteen bins, zero counts, and totals as simple and
complex increments. Zero-day responses count in 1–20.
`ExpeditedResponseTimeIncrementsSection` follows the complex section, with
`ERT1`, `ERT2`, etc. linked to components and `ERT0` linked to the agency.

## Pending perfected requests

`PendingPerfectedRequestsAggregator` includes rows with J populated and K blank,
separated by Track S, C, and E. There is no disposition filter. Age is working
days from J through September 30 of the report year, excluding the start day
and including the end day, without fiscal-start clamping or toll subtraction.
The shared `WorkingDays` calendar excludes weekends and federal holidays.

Frequency maps provide counts, exact medians, and averages per component and
across all agency rows. Empty tracks emit quantity 0 and literal `N/A` in both
median/average value elements; populated averages have two decimals. Same-day
ages remain numeric zero. `PendingPerfectedRequestsSection` follows expedited
increments and uses `PPR1`, `PPR2`, etc. and agency `PPR0` organization links.

## Oldest pending requests

`OldestPendingRequestAggregator` selects rows with I populated and K blank,
without track or disposition filters. Pending age uses the shared working-day
calendar from the actual received date through fiscal year-end, excluding the
start day and including the end day. Prior-year time is included.

At most ten items are retained per component and for the entire agency, sorted
by descending pending days, then ascending receipt date for ties. Duplicate
rows remain separate entries. Empty components retain their empty container
and association. `OldestPendingRequestSection` follows pending perfected
requests, with ISO receipt dates and `OPR1`, `OPR2`, etc.; `OPR0` is the agency.
The existing oldest-appeal section continues using calendar days.

## Expedited processing

`ExpeditedProcessingAggregator` counts uppercase G and D in Column S as granted
and denied. With both Q and R present, adjudications taking at most ten working
days also increment the timely counter. The shared calendar excludes the start
day, includes the end day, and skips weekends and listed federal holidays.
Same-day determinations qualify. Blank R still counts the outcome, but not a
timely adjudication. Invalid or reversed dates produce contextual exceptions.

`ExpeditedProcessingSection` follows oldest pending requests. All three counts,
including zeros, appear for each component and the agency. `EP1`, `EP2`, etc.
and `EP0` link to their Organization entries. Agency counts sum components.

## Fee waivers

`FeeWaiverAggregator` counts uppercase G and D in Column V for every component
and sums those counts for the agency. Other values and blank cells do not
contribute. No date, track, or disposition filter is applied.
`FeeWaiverSection` follows expedited processing, emitting both granted and
denied quantities even when zero. `FW1`, `FW2`, etc. reference components;
`FW0` references the agency through `FeeWaiverOrganizationAssociation`.

For appeal rows (Column X populated), CSV validation requires E–P and T–W
to be blank. Q, R, and S may contain expedited-processing data and retain
their date and dependency checks. Appeal rows are exempt from the rule that
S = G requires M = E, since M must remain blank on these rows.

## Personnel and cost placeholders

`PersonnelAndCostSection` follows fee waivers. Every uploaded component and
the agency receive all six personnel/staffing/cost fields with literal `N/A`,
since the CSV has no source data for these values. `PC1`, `PC2`, etc. link to
component organizations; `PC0` links to the agency. No CSV pass is needed.

## Fees collected

`FeesCollectedAggregator` sums Column W across all rows without track, outcome,
or date filters. Blank cells contribute zero. Plain decimal amounts with up
to two decimal places (and optional sign) are summed as integer cents to avoid
floating-point drift. Unsupported formats and overflow produce contextual
exceptions rather than silently dropping or rounding data.

`FeesCollectedSection` follows personnel and cost. Each component and the agency
receive a two-decimal `FeesCollectedAmount`, including zero, and a fixed
`FeesCollectedCostPercent` of `0.0000`. `FC1`, `FC2`, etc. and agency `FC0`
use the usual Organization associations.
