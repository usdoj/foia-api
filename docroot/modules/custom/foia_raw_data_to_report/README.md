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

Each processing attempt clears previous messages. `CsvValidator::validate()`
streams the CSV one record at a time and checks for 29 columns, matching
`OIP Request Raw Data (FOIA Star submission) (final).csv`. Both the header and
data records are checked. Blank records are skipped; quoted commas, escaped
quotes, and multiline values are supported. Record numbers include the header
and blank records and are not physical line numbers for multiline CSVs.

The first invalid record produces a human-readable message with the expected
and actual column counts. Missing, unreadable, empty, and non-CSV uploads also
produce messages. Validation failures finish the queue item without generating
XML or changing any existing XML attachment. Correct the CSV and click Generate
XML Report again to retry. Successful validation sets Messages to **CSV validated.**

Future CSV checks belong in `CsvValidator::validate()`. Header names and the
contents of individual columns are not validated yet. The reference CSV is not
needed at runtime; its 29-column count is recorded in `EXPECTED_COLUMNS`.

Run validator unit tests with:

```bash
ddev exec vendor/bin/phpunit -c docroot/core/phpunit.xml.dist docroot/modules/custom/foia_raw_data_to_report/tests/src/Unit/CsvValidatorTest.php
```
