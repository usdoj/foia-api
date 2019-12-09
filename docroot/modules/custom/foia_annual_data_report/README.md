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
