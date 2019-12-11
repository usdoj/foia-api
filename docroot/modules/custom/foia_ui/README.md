FOIA UI CUSTOM MODULE
=====================

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Documentation
   - OpenAPI docs
   - Client-side validation of annual report fields
   - Utility JavaScript functions
   - Admin theme overrides


INTRODUCTION
------------

The FOIA UI module contains custom user interface modifications for the DOJ FOIA
project. This includes the following:

 * OpenAPI docs generation and configuration
 * Client-side validation of annual report fields
 * Utility JavaScript functions
 * Admin theme overrides


REQUIREMENTS
------------

The jQuery Validation library is an NPM dependency added to the DOJ FOIA
site composer.json via [Asset Packagist](https://asset-packagist.org/).


INSTALLATION
------------

FOIA UI is a custom Drupal module so unlike contrib modules, the codebase is not
installed via composer. Enable as you would normally enable a contributed Drupal
module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules#s-step-2-enable-the-module
for further information.


DOCUMENTATION
-------------


### Open API docs

// @todo Document FOIA UI API docs


### Client-side validation of annual report fields

The "Annual FOIA Report Data" content type makes heavy use of client-side
validation using the [jQuery Validation](https://jqueryvalidation.org/) library.
This provides immediate inline validation error messages well as allowing the
form to be saved with invalid fields.

Validation rules in `foia_ui.validation.js` follow the sequential order of
fields on the annual report content type. Most (if not all) of the rules are
constructed using custom methods. For more information on custom methods, see
https://jqueryvalidation.org/jQuery.validator.addMethod/.

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


### Admin theme overrides

The DOJ FOIA project uses the Drupal core Seven administration theme. FOIA UI
adds a stylesheet containing various CSS overrides for accessibility and
usability enhancements primarily for adding and editing the "Annual FOIA Report
Data" content type.

The content editor secondary region (Last saved, Revision log message, etc.) has
been moved to the bottom of the content fields at screen sizes ≤ 1500px to
accommodate nested vertical tabs.
