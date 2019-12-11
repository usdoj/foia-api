TABLE OF CONTENTS
-----------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Usage
 * The queue
 * Configuration
   - Post-installation configuration
   - Updating and adding report sections
   - Adding a section to the foia_agency_report migration
   - Special sections
 * Troubleshooting


INTRODUCTION
------------

The FOIA Upload XML module enables agencies to upload annual reports to the 
FOIA site via NIEM-XML formatted annual report documents.

The module creates a new Annual Report node per agency and report year or 
updates an already existing report for the corresponding agency and report year.


REQUIREMENTS
------------

This module requires the core Migrate module along with the following contrib 
modules:
 * Migrate Plus (https://www.drupal.org/project/migrate_plus)
 * Migrate Tools (https://www.drupal.org/project/migrate_tools)
 
For development, maintenance, and troubleshooting installing Drush is strongly 
recommended.


RECOMMENDED MODULES
-------------------

 * FOIA Migrate: Custom module in this codebase. When enabled, FOIA Migrate
 can be used to import and create Agencies and Agency Components that may
 be referenced in the migrations run during upload of a report file from
 this module.
 * Queue UI (https://www.drupal.org/project/queue_ui): When enabled, users
 with the permission Administer queues can inspect items in a queue, remove
 leases on items in a queue, or clear a queue.

INSTALLATION
-------------

FOIA Upload XML is a custom Drupal module so unlike contrib modules, the
codebase is not installed via composer. Enable as you would normally enable a
contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules#s-step-2-enable-the-module
for further information.


USAGE
-----

Once installed, users with `create annual_foia_report_data content` permission 
can upload report files to create a new annual report or update an existing 
report. Users may navigate to `/report/upload` to find the Annual Report upload 
form. Or in the admin UI, go to the `Admin -> Content` section 
(`/admin/content`) then click the `Upload Annual FOIA Report Data` tab.

Use the Annual Report field to navigate to the Annual Report XML file on the 
local system. Click the `Submit` button to initiate the upload. A progress bar 
will display as the section migrations are executed. If an upload is already in 
progress a message indicating `Your report has been queued...` will appear. 

Once complete navigate to the Content section to find new or updated report. 
Should any errors or other issues occur with the upload, users should contact 
their Agency Managers or Agency Administrators.


THE QUEUE
---------

In the event that an import process is already running when a user uploads a
report file, the new report will be added to the
`foia_xml_report_import_worker` queue. The queue worker for this queue is
defined in `FoiaXmlReportImportWorker.php`. It will run on cron, processing
as many queued reports as possible in 10 minutes.


CONFIGURATION
-------------


### Post-installation configuration

Upon installation of the FOIA Upload XML module users with 
`create annual_foia_report_data content` permission will be able to upload XML 
report files for their assigned agency. If using the Queue UI module, users
with the `Administer queues` permission will be able to inspect and alter
queues and queue items. No further configuration will be required.

Should the report format be updated the following section will describe how the 
report migration mappings are currently setup and how new mappings might be 
added should new report sections and/or fields be added.


### Updating and adding report sections

#### Overview

This module defines multiple migrations for importing a full annual report. It 
is built on Drupal's Migrate API and leverages Migrate Plus and Migrate Tools.

The migrations are defined by the following:

 * `config/install/*.yml`: configuration files defining custom migrations
 * `src/Form/AgencyXmlUploadForm.php`: define a custom form for uploading the
  XML
  report
 * `Plugin/migrate/process/*.php`: custom process plugins used by the migrations

Additional files in the module implement batch processing of the custom 
migrations and the import queue.


#### Custom migrations

There are a lot of files in the `/config/install/` directory, but it is not
too complicated once you understand how they are organized.

By group they are:
 * `foia_xml`: Main migrations - annual report node (`foia_agency_report`) with 
overall data and the agency components (`component`) import.
 * `foia_component_data_mapping`: agency component data mappings - maps the 
agency component identifiers from the import file to the report section data.
 * `foia_component_data_import_subs`: data import for sub-sections of sections 
IV, V.B.2, and VI.C.3 per component. 
 * `foia_component_data_import`: data import for report sections per component.


#### Beginning and end

##### Main report migration - `foia_agency_report`

The file `migrate_plus.migration.foia_agency_report.yml` defines the main report
import and creates a single `annual_foia_report_data` node from the XML report. 
It depends on all the other migrations, so it will only run when they are 
complete.


##### Agency component mapping - `component`

The XML file has a section that associates internal identifiers to each of the
component abbreviations. It looks something like this:

```
  <nc:Organization s:id="ORG0">
    <nc:OrganizationAbbreviationText>USDA</nc:OrganizationAbbreviationText>
    <nc:OrganizationName>United States Department of Agriculture</nc:OrganizationName>
    <nc:OrganizationSubUnit s:id="ORG1">
      <nc:OrganizationAbbreviationText>AMS</nc:OrganizationAbbreviationText>
      <nc:OrganizationName>Agricultural Marketing Service</nc:OrganizationName>
    </nc:OrganizationSubUnit>
    ...
  </nc:Organization>
```

In this example, the internal identifiers "ORG0" and "ORG1" are associated to
the agency (USDA) and one of its components (AMS), respectively.

The file `migrate_plus.migration.component.yml` defines a migration that does
not create any entities. It just creates a map from the internal identifier to
the corresponding `agency_component` node. Other migrations use this map via
the `migration_lookup` process plugin, so all the other migrations depend on
this one.

More precisely, the `component` migration creates a map from the three keys
report year, agency abbreviation, internal identifier to the node ID of the
corresponding `agency_component` node. The first two keys are used to identify
the current XML report, or the corresponding `annual_foia_report_data` node.


#### The middle

For each paragraph field (and associated "overall" fields) we add two
migrations.


##### Component data mapping

The first is similar to the `component` migration, and depends directly on it.
For example, Section V.A of the report contains a section like this:

```
  <foia:ProcessedRequestSection>
    ...
    <foia:ProcessingStatisticsOrganizationAssociation>
      <foia:ComponentDataReference s:ref="PS19" />
      <nc:OrganizationReference s:ref="ORG19" />
    </foia:ProcessingStatisticsOrganizationAssociation>
    <foia:ProcessingStatisticsOrganizationAssociation>
      <foia:ComponentDataReference s:ref="PS0" />
      <nc:OrganizationReference s:ref="ORG0" />
    </foia:ProcessingStatisticsOrganizationAssociation>
    ...
  </foia:ProcessedRequestSection>
```

Withing this section, "PS0" and "PS19" are associated to the internal
identifiers "ORG0" and "ORG19", respectively: these are the identifiers
handled by the `component` migration. The file
`migrate_plus.migration.component_requests_va.yml` is used to map these
section-specific identifiers "PS0" and "PS19" and so on to the corresponding
`agency_component` nodes.


##### Component data import

The second migration for this section is defined by the file
`migrate_plus.migration.foia_requests_va.yml`. This migration creates
Paragraphs of type `foia_req_va`. These Paragraphs are then referenced in
`field_foia_requests_va` in the `foia_agency_report` migration, using the
`migration_lookup` process plugin.


### Adding a section to the foia_agency_report migration

[The middle](#the-middle) describes how a section's component data is
imported into paragraph items from a corresponding section of the XML file. In
addition to this, we also need to ensure that the section data gets properly
imported in the `foia_agency_report` migration. (There are some nested
Paragraphs, and handling those will be a little different.  See `Special
sections` below.)

To do this, the `foia_agency_report` migration in
`migrate_plus.migration.foia_agency_report.yml` needs to be updated to import
the section's "agency overall" data and attach the imported paragraph items to
the annual report node. The basic process looks like the following:

 1. Add fields in the `source/fields` section.
 2. Map agency overall fields in the `process` section. Remember that most
   destination fields have the prefix `field_`.
 3. Attach component data to the annual report node, using the
   `migration_lookup` process plugin to find and reference Paragraph's
   imported in the section migration.
 4. Add your Paragraph migration to the list of dependencies at the end of the
   file.

Continuing with the example section V.A and the component data imported in the
migration `migrate_plus.migration.foia_requests_va.yml`, the following
details how that section would be added and processed in the
`foia_agency_report` migration.

#### 1. Add fields in the `source/fields` section

Step 1 is a little tricky, since you have to find the correct XPath
expressions to extract the data from the XML. Since the `foia_agency_report`
migration includes both component specific data and agency overall data, you
will likely have to add both overall data and references to component
data as sources in the `foia_agency_report` migration.

The "overall" fields are closely related to the corresponding per-component
fields. For example, `migrate_plus.migration.foia_requests_va.yml` includes
the following:

```
source:
  item_selector: '/iepd:FoiaAnnualReport/foia:ProcessedRequestSection/foia:ProcessingStatistics'
  fields:
    -
      name: field_req_pend_start_yr
      label: 'Requests pending at the start of the year'
      selector: 'foia:ProcessingStatisticsPendingAtStartQuantity'
```

The full XPath selector, when combining the `item_selector` and the `selector`
for this field, gives (adding line breaks for readability)

```
  /iepd:FoiaAnnualReport
  /foia:ProcessedRequestSection
  /foia:ProcessingStatistics
  /foia:ProcessingStatisticsPendingAtStartQuantity
```

Compare this to selecting the agency overall data for this same element
in `migrate_plus.migration.foia_agency_report.yml`:

```
source:
  item_selector: '/iepd:FoiaAnnualReport'
  fields:
    -
      name: overall_req_pend_start_yr
      label: 'Overall requests pending at the start of the year'
      selector: 'foia:ProcessedRequestSection/foia:ProcessingStatistics[@s:id="PS0"]/foia:ProcessingStatisticsPendingAtStartQuantity'
```

The full XPath selector for this field's agency overall data is:

```
  /iepd:FoiaAnnualReport
  /foia:ProcessedRequestSection
  /foia:ProcessingStatistics[@s:id="PS0"]
  /foia:ProcessingStatisticsPendingAtStartQuantity
```

The only difference is the additional selector `[@s:id="PS0"]`.

In addition to the section's agency overall fields, the section's component
data must also be added as a source field in the `foia_agency_report`
migration. In this example, adding section V.A's component data as a source
looks like the following:

```
  name: component_va
  label: 'Internal index of the agency component'
  selector: 'foia:ProcessedRequestSection/foia:ProcessingStatistics/@s:id'
```


#### 2. Map agency overall fields

Continuing with the example of adding data from section V.A to the
`foia_agency_report`, there is some agency overall data that needs to be
 mapped in the migration's `process` section.

Agency overall data can be mapped relatively simply. Often the data can be
set directly as the field value like so:

```
field_overall_req_pend_start_yr: overall_req_pend_start_yr
```

More processing of overall data can be done if required. A common option is
to set the field value along with a default value.


#### 3. Map Paragraph fields using the `migration_lookup` process plugin

Mapping component data is slightly more complex. The processing pipeline
that attaches the paragraph items imported in
`migrate_plus.migration.foia_requests_va.yml` to the agency report node looks
like this:

```
  field_foia_requests_va:
    -
      plugin: foia_array_pad
      source: component_va
      prefix:
        - report_year
        - agency
    -
      plugin: sub_process
      process:
        combined:
          plugin: migration_lookup
          source:
            - '0'
            - '1'
            - '2'
          migration:
            - foia_va_requests
          no_stub: true
        target_id:
          plugin: extract
          source: '@combined'
          index:
            - '0'
        target_revision_id:
          plugin: extract
          source: '@combined'
          index:
            - '1'
```

This first part of the process pipeline gets data from the source field
`component_va` and transforms it to array values that can be used in the
migration lookup sub-process.

```
  field_foia_requests_va:
    -
      plugin: foia_array_pad
      source: component_va
      prefix:
        - report_year
        - agency
```

The source field `component_va` is an array of strings, like

```
["PS1", "PS2", ... ]
```

The `foia_array_pad` plugin is custom: it adds the source fields listed under
`prefix`, producing an array of arrays:

```
[[2018, "USDA", "PS1"], [2018, "USDA", "PS2"], ... ]
```

The next step in the process pipeline is to apply `migration_lookup` to each
of those triples created by the `foia_array_pad` plugin:

```
    -
      plugin: sub_process
      process:
        combined:
          plugin: migration_lookup
          source:
            - '0'
            - '1'
            - '2'
          migration:
            - foia_requests_va
          no_stub: true
```

This results in an array of arrays. The inner arrays have a single key,
`combined`, and the corresponding value is the result of applying
`migration_lookup` for the `foia_requests_va` migration to the triple from the
preceding step. Since that is a Paragraph migration, this value is an array of
two numbers (entity ID and revision ID).

Still within the`sub_process` plugin, we have

```
        target_id:
          plugin: extract
          source: '@combined'
          index:
            - '0'
        target_revision_id:
          plugin: extract
          source: '@combined'
          index:
            - '1'
```

These processes extract their respective values from the migration
lookup. At this point, the original array of values from the `component_va
` source field:

```
["PS1", "PS2", ... ]
```

Has been processed into an array that can be used to set the value, or values,
of the `field_foia_requests_va` paragraph reference field:

```
[
  [
    'combined' => [123, 456],
    'target_id' => 123,
    'target_revision_id' => 456,
  ],
  [
    'combined' => [678, 789],
    'target_id' => 678,
    'target_revision_id' => 789,
  ]
]
```

As the `field_foia_requests_va` field is a reference field to paragraph
entities, the migration expects a `target_id` and a `target_revision_id` in
order to set the field value, and the `combined` value is ignored.


#### 4. Add the section migration to the agency report migration's dependencies

Add the new section's migration to the `foia_agency_report` migration's
dependencies:

```
migration_dependencies:
  required:
    - foia_va_requests
```

#### Adding new section to batch process

The multiple migrations defined above are batched and run in the required order 
to successfully import a new Annual Report. To include a newly added section to 
the batch process, the new component-to-data mapping and component data import 
migrations will need to be added to the batch list.

The `FoiaUploadXMLMigrationsProcessor.php` defines the function 
`getMigrationList` which provides the list of migrations to be run to the batch 
processor. The new component-to-data mapping and component data import migration
definitions will need to be added to their corresponding grouping in the 
`$migrations_list`: 

```php
// NOTE: Inline comments added.
  /**
   * Fetches an array of migrations to run to import the Annual Report XML.
   *
   * @return string[]
   *   List of migrations.
   */
  public function getMigrationsList() {
    $migrations_list = [
      // component internal identifier to system ID mapping migration
      'component',
      // component-to-data mapping migrations
      'component_iv_statutes',
      'component_va_requests',
      //...
      // component sub-section data import migrations
      'foia_vb2_other',
      'foia_vic3_other',
      'foia_iv_details',
      //...
      // component data import migrations
      'foia_iv_statute',
      'foia_va_requests',
      //...
      // foia annual report import migration
      'foia_agency_report',
    ];

    return $migrations_list;
  }
```


### Special sections

Note that for certain sections, a sub-paragraph is implemented to capture data 
provided within the section.

Each case will be different so for examples look at the migrations for 
section IV where:
 * sub-import: foia_iv_details
 * main-import: foia_iv_statute

or section V.B.2 where:
 * sub-import: foia_vb2_other
 * main-import: foia_vb2


TROUBLESHOOTING
---------------

### Import data from agency annual reports in NIEM-XML format

In general, updating or adding a new migration can be tested using the admin UI.

After making changes in the `config/install/` directory, the usual workflow is

```
drush cr
drush cim --partial --source=modules/custom/foia_upload_xml/config/install
drush cex
```

Testing and troubleshooting the migration is helped with drush commands 
implemented by the Migrate and Migrate Tools modules.

When testing, you can repeat the first two steps as needed and only export
configuration when you are ready to commit.

This workflow creates two nearly identical copies of the configuration files:
on in the module's `config/install/` directory and one in the site's
configuration directory. There are at least two advantages to this redundancy:

 1. We can add comments to the files in the module. Comments are stripped from
   the files generated by `drush cex`.
 2. It provides a safeguard in case someone else deletes files in the site
   configuration directory.


### Running the migrations

#### Via the UI

One option is to run the migrations through the admin UI. The upload form at
`/report/upload` has a link to the relevant page, and it also redirects there
after uploading the file.


#### Via the migrate import commands

The other option is to use the drush migrate commands on the command line:

```
drush @foia.local ms; drush @foia.local mim -vvv --debug component; drush @foia.local mim -vvv --debug --group=foia_component_data_mapping; drush @foia.local mim -vvv --debug --group=foia_component_data_import_subs; drush @foia.local mim -vvv --debug --group=foia_component_data_import; drush @foia.local mim -vvv --debug foia_agency_report --update; drush @foia.local ms
```

This combines the running of the following import commands with the checking of
the migration status before and after:

```
drush @foia.local mim -vvv --debug component
drush @foia.local mim -vvv --debug --group=foia_component_data_mapping
drush @foia.local mim -vvv --debug --group=foia_component_data_import_subs
drush @foia.local mim -vvv --debug --group=foia_component_data_import
drush @foia.local mim -vvv --debug foia_agency_report --update
```

 * Use `drush ms --group=foia_xml` to check the status of the migrations.
 * To see available import options, use `drush help mim`.


### Processing the queue


#### Via the Queue UI module

Issues with the queue may not be obvious when run in the production environment.
In addition to information found in the logs, the Queue UI module defines
admin screens that can be used to inspect and clear queues, or remove leases
on queue items.  This is useful if there are items that are stuck in the
queue and need to be cleared out and re-processed or re-uploaded.  This can be
found at `/admin/config/system/queue-ui`.


#### Via the command line

The queue can be run by running cron on the command line:

`drush @foia.local core:cron`
