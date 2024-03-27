FOIA ADVANCED AUTO CALCULATIONS CUSTOM MODULE
=============================================

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Documentation
   - General structure
   - jQuery selector reference
   - Utility JavaScript functions


INTRODUCTION
------------

The FOIA Advanced Auto Calculations module allows specific fields on the "Annual
FOIA Report Data" content type to be automatically calculated on the client-side
using custom calculations. These calculations are executed in batch on the
initial form load and individually as the related fields are changed by a user.

See documentation section for the following:

 * General structure
 * jQuery selector reference
 * Utility JavaScript functions

See the FOIA Autocalc module for configuration-based simple sum automatic
calculations.


REQUIREMENTS
------------

This module requires the following modules:

 * FOIA UI custom module

Additionally, the following core JavaScript libraries and plugins are required:

 * Drupal
 * DrupalSettings
 * jQuery
 * jQuery Once (core/once drupal 10)


INSTALLATION
------------

FOIA Advanced Auto Calculations is a custom Drupal module so unlike contrib
modules, the codebase is not installed via composer. Enable as you would
normally enable a contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules#s-step-2-enable-the-module
for further information.


DOCUMENTATION
-------------

The FOIA Advanced Auto Calculations module attaches the `advcalc-fields.js` file
to the "Annual FOIA Report Data" edit/add form.


### General structure

`advcalc-fields.js` is organized as follows:

 * Utility functions
 * jQuery object calculation functions
 * Annual Report field jQuery selector calculation bindings
   * Initialize on load (for each binding)
   * Calculate on change (for each binding)


### jQuery selector reference

A common convention used to select fields within a specific paragraph instance
is via the name attribute, e.g. for the following markup:

```html
<input … name="field_foia_requests_va[1][subform][field_req_pend_start_yr][0][value]" …>
```

Where:

 * `field_foia_requests_va` is the paragraph type machine name.
 * `[1]` indicates that it's the second instance of the paragraph component.
 * `field_req_pend_start_yr` is the field machine name.
 * `[0]` indicates the first, and typically only, instance of the field on the
 paragraph instance.

The jQuery selector for all instances of the field is:

```js
$("input[name*='field_foia_requests_va']").filter("input[name*='field_req_pend_start_yr'])
```


### Utility JavaScript functions

Several JavaScript text-processing functions are used in common between
`foia_ui` validation and `foia_advcalc` auto-calculation. They can be accessed
as methods on the `Drupal.FoiaUI` object, e.g.

```js
Drupal.FoiaUI.specialNumber("<1");
// returns 0.1
```
