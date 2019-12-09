CONTENTS OF THIS FILE
---------------------

 * Introduction
   * Events triggering calculations
   * Settings structure and calculations
 * Recommended modules
 * Installation
 * Configuration


INTRODUCTION
------------

The FOIA Autocalc module allows administrators to configure a field to
automatically calculate its value as the sum of one or more fields existing
on the same node.


### Events triggering calculations

Calculations are done in JavaScript on the following events:

 * Page load: When the add or edit form is first loaded and behaviors are
 attached, the module will attempt to calculate the value of any auto-calculated
 field.
 * Change: When a field that is an addend for any auto-calculated field is
 changed, the calculations will be run again, summing any fields that are
 dependent on the changed field.


### Settings structure and calculations

In order to auto-calculate values in JavaScript, auto-calculation settings
are exported to `drupalSettings.foiaAutocalc.autocalcSettings` as a js object of
field machine names as keys whose value is an array of all fields that can be
used to calculate a field with that machine name. The structure will look
something like the following:

```
{
  'field_overall_viic1_1_20_days': [
    {
      "field": "field_proc_req_viic1",
      "subfield": {
        "field": "field_1_20_days"
      },
     "this_entity": 0
    },
  ],
  'field_total': [
    {
      "field": "field_admin_app_vib",
      "subfield": {
        "field": "field_affirmed_on_app"
      },
     "this_entity": "1"
    },
    {
      "field": "field_admin_app_vib",
      "subfield": {
        "field": "field_part_on_app"
      },
     "this_entity": "1"
    },
    {
      "field": "field_foia_requests_vb1",
      "subfield": {
        "field": "field_rec_ref_to_an_comp"
      },
     "this_entity": "1"
    },
    {
      "field": "field_foia_requests_vb1",
      "subfield": {
        "field": "field_dup_request"
      },
     "this_entity": "1"
    },
    ...
  ]
}
```


#### Calculations constrained to "this_entity"

Consider this section of the above example that contains the `field_total`
configurations.

```
{
  ...
  'field_total': [
    {
      "field": "field_admin_app_vib",
      "subfield": {
        "field": "field_affirmed_on_app"
      },
     "this_entity": "1"
    },
    {
      "field": "field_admin_app_vib",
      "subfield": {
        "field": "field_part_on_app"
      },
     "this_entity": "1"
    },
    {
      "field": "field_foia_requests_vb1",
      "subfield": {
        "field": "field_rec_ref_to_an_comp"
      },
     "this_entity": "1"
    },
    {
      "field": "field_foia_requests_vb1",
      "subfield": {
        "field": "field_dup_request"
      },
     "this_entity": "1"
    },
    ...
  ]
}
```

This is a partial example of the configuration for `field_total` fields on an
Annual FOIA Report Data node. The following is true of this example:

 * `field_admin_app_vib` is a unlimited cardinality reference field to a
 paragraph item entity that contains the fields `field_affirmed_on_app`,
 `field_part_on_app`, and `field_total`.
 * `field_foia_requests_vb1` is a unlimited cardinality reference field to a
 paragraph item entity that contains the fields `field_rec_ref_to_an_comp`,
 `field_dup_request`, and `field_total`.
 * Each field configured as an addend for `field_total` has a `this_entity`
 value of 1 (or true), so the `field_total` values should only be calculated
 from field values that are contained on the same entity as the `field_total`
 field, granular to the paragraph item level.

When the auto-calculations are run for `field_total` the following
calculations will occur in this example.

 * The value of each `field_total` field that is a child of a
 `field_admin_app_vib` paragraph item will be calculated as the sum of that
 same paragraph item's `field_affirmed_on_app` and `field_part_on_app` fields.
 * The value of each `field_total` field that is a child of a
 `field_foia_requests_vb1` paragraph item will be calculated as the sum of that
 same paragraph item's `field_rec_ref_to_an_comp` and `field_dup_request`
 fields.


#### Calculations not constrained to "this_entity"

Consider this section of the above example that contains the
`field_overall_viic1_1_20_days` configurations.

```
{
  'field_overall_viic1_1_20_days': [
    {
      "field": "field_proc_req_viic1",
      "subfield": {
        "field": "field_1_20_days"
      },
     "this_entity": 0
    },
  ],
  ...
```

This is an example of the configuration to calculate an agency overall field
value on an Annual FOIA Report Data node. The following is true of the example:

 * `field_overall_viic1_1_20_days` is a single cardinality field that exists
 directly on the Annual FOIA Report Data node form.
 * `field_proc_req_viic1` is an unlimited cardinality reference field to
 a paragraph item entity that contains the field `field_1_20_days`.
 * The configured addend field's `this_entity` property value is 0, not
 restricting this calculation to field values from the same entity. It will
 therefore sum the values of all `field_1_20_days` fields.

When auto-calculations are run for `field_overall_viic1_1_20_days`, the
following calculations will occur in this example:

 * The value of `field_overall_viic1_1_20_days` will be calculated as the
 sum of the values of all `field_1_20_days` fields that exist on the form.


RECOMMENDED MODULES
-------------------

 * Field UI: When enabled, auto-calculation settings can be configured on field
 forms.


INSTALLATION
------------

FOIA Autocalc is a custom Drupal module so unlike contrib modules, the codebase
is not installed via composer. Enable as you would normally enable a
contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules#s-step-2-enable-the-module
for further information.


CONFIGURATION
-------------

In order to configure auto-calculated fields, users must have permission to
administer fields for a given entity. Configure the following or similar user
permissions in Administration » People » Permissions:

 * Content: Administer fields
 * Paragraph: Administer fields

To configure a field as auto-calculated, edit or create a field on an entity.
At the bottom of the field form, there will be a section titled
"Automatically Calculated Value" where one or more fields can be configured as
addends for calculating the value of the field being configured. The parts
of the configuration are:

 * Field: The machine name of a field that should be summed to create the
 current field's value. Occasionally the autocomplete list will not include
 the field you are looking for. Continue by manually entering the machine
 name of the field you intend to add if you do not find it in the autocomplete
 list.
 * This entity: A checkbox indicating whether the calculation should only use
 this field value if it exists on the same entity as the field being
 configured. For example, if the field being calculated is attached to a
 paragraph item and the addend field's "This entity" checkbox is checked, the
 auto-calculation will only use addend field values that exist on the same
 paragraph item as the calculated field.
