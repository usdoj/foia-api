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

`RawDataToReportProcessing::generateXmlReport()` is the conversion stub. It
currently writes a **zero-byte placeholder**, not valid XML, and attaches it to
`field_request_data_xml`. It uses the field's configured directory and storage
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
The CSV is validated before the placeholder XML is generated; conversion is not
implemented yet.

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
  K may be empty; date and other column requirements will be added later.
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
locked or snapshotted. CSV conversion remains a stub, producing one empty XML
only after every component upload passes validation.

Local integration verification (creates and removes temporary fixtures):

```bash
ddev drush php:script docroot/modules/custom/foia_raw_data_to_report/tests/integration/component_uploads.php
```

Browser and regression verification:

```bash
ddev behat -f RawDataToReport.feature
```
