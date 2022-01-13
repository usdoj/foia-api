CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

The FOIA Annual Data Report module is a utility module that implements form 
tweaks for the Annual Data Report add/edit form and custom configuration, 
including Annual Report Memory Limit, that aid in editing to the annual report 
data.


REQUIREMENTS
------------

FOIA Annual Data Report has no contrib or custom module requirements.
It does have library dependencies on:

 * core/jquery
 * core/drupalSettings


INSTALLATION
------------

FOIA Annual Report Data is a custom Drupal module so unlike contrib modules,
the codebase is not installed via composer. Enable as you would normally
enable a contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules#s-step-2-enable-the-module
for further information.


CONFIGURATION
-------------

In order to change this module's configuration, users must have permission to
administer site configuration. Configure permissions in Administration
» People » Permissions:

 * Administer site configuration

To configure the Annual Report Memory limit go to:
/admin/config/system/foia_annual_data_report_memory_limit

 * Fill in the `Annual Report Memory Limit` field  if you wish to override the
default PHP Memory Limit set for the platform. The value set in the `Annual
Report Memory Limit` field must be in the form of a numeric value and a
capitalized M as the unit symbol, with no space between the two, e.g. `1024M`.
 * Check the `Debug Annual Report memory limit` checkbox to log the memory
limit before the node is saved, while it is being saved, and after it has been
saved.

NODE SAVE PROTECTION
--------------------

The newer split form has a reminder to save when moving from one section to
another.  We had to disable this because it did not trigger reliably, and
sometimes triggered when it shouldn't.

If revisited, look a the JS file in the form_autosave contrib module for what
might be a good example of detecting when a drupal form has changed.

This feature was disabled by just removing the js file from the
foia_annual_data_report.libraries.yml file.  To restore it, add these lines
back in:

foia_node_edit_protection:
  js:
    js/foia-node-edit-protection.js: {}
  dependencies:
    - core/jquery
    - core/drupal
