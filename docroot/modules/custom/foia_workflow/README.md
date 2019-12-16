CONTENTS OF THIS FILE
---------------------

 * Introduction
   * Access restrictions
   * Access grants
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

The FOIA Workflow module adds custom access restrictions and grants to Annual
FOIA Report Data nodes based on a user's agency and role, and the workflow
state of the node.


### Access restrictions

 * Users with the role Agency Administrator are not allowed to edit a report
  that is authored by another user if it is still in a `draft` state.
 * Users with the role Agency Manager are not allowed to edit or delete a report
  that is not in either the `draft` or `back_with_agency` state.


### Access grants

 * Users with the role Agency Manager are allowed to edit or delete a
  report that has been authored by a user in their agency, if the report is
  in the `draft` or `back_with_agency` state.


REQUIREMENTS
-------------

 * Core Workflows module: Access restrictions are based on workflow states
  defined by the "Annual Report Workflow".
 * Access rules are for the `annual_foia_report_data` content type.
 * Some access rules are based on a user and report's `field_agency`, which
  references a taxonomy term defined in the Agency vocabulary.
 * Access rules are based on the roles `agency_administrator` and
  `agency_manager`.


INSTALLATION
------------

FOIA Workflow is a custom Drupal module so unlike contrib modules, the codebase
is not installed via composer. Enable as you would normally enable a
contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules#s-step-2-enable-the-module
for further information.


CONFIGURATION
-------------

The custom access grants and restrictions in this module are partly based on a
user's role and agency. To grant or restrict access based on the rules in
this module:

 * Assign roles to users at Administration » People:
   * Agency Administrator
   * Agency Manager
 * Assign an Agency to users at `/user/[uid]/edit` in the Agency field.
