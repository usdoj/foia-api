Webform Template
=================

### About this module

This module provides an alternative approach to the webform templates module
included with webform. Rather than designating one or more freely cloneable
templates, this module provides the ability to set a global template that can be
enabled or disabled for each webform, given sufficient permissions. When enabled, 
the set of elements provided by the template are added to new webforms and 
cannot be removed or edited unless the template is later disabled.

### Creating the template

The template is configured using the simple config
`webform_template.settings.webform_template_elements`
The value of this config should be a string of yaml, corresponding to the
`elements` property of a webform configuration entity.

To create or update the template, the following steps can be used.
  - Create and configure a webform to contain the webform elements desired for
    the template.
  - Export the configuration from the webform and copy the value of the
    `elements` property from the exported config (`webform.webform.*.yml` where
    `*` is the machine name of the webform).
  - Replace the value of `webform_template_elements` in
    `webform_template.settings.yml` with the copied value, then import the 
    updated config.

### Installation
Consult the online documentation at
https://www.drupal.org/docs/8/extending-drupal-8/installing-modules for
installation instructions.
