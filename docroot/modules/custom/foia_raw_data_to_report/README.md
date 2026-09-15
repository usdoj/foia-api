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
The CSV is not read, parsed, or converted yet.
