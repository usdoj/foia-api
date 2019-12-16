CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Recommended modules
 * Installation
 * Configuration


INTRODUCTION
------------

The FOIA Readonly module adds a [readonly attribute](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/readonly)
to input elements of fields that are autocalculated and therefore should not be
editable by the user.  The module adds this attribute to auto-calculated
fields on Annual FOIA Report Data node forms and any child widgets such as
paragraph items.

Auto-calculated fields are marked readonly based on the list of field machine
names maintained in this module.  In order to make a field readonly, add a
field's machine name to the list.  Readonly fields defined in paragraph items
are disabled per paragraph type in the function
`foia_readonly_field_widget_multivalue_entity_reference_paragraphs_form_alter`.


RECOMMENDED MODULES
-------------------

The following modules enable or define calculations on Annual FOIA Report Data
fields that make it desirable for these fields to be marked as read only.

 * `foia_autocalc`: Allows a field to define the numeric fields that should be
 used to auto-calculate its value.
 * `foia_advcalc`:  Defines custom calculations for specific fields.


INSTALLATION
------------

FOIA Readonly is a custom Drupal module so unlike contrib modules, the codebase
is not installed via composer. Install as you would normally install a
contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules#s-step-2-enable-the-module
for further information.


CONFIGURATION
-------------

There is no configuration for this module.  Users with permissions to edit or
create Annual FOIA Report Data nodes will see that the fields defined as
readonly by this module are disabled on the node edit and create forms.
