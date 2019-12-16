CONTENTS OF THIS FILE
---------------------

 * Introduction
   * Exporting annual report nodes
   * The export class
   * Export structure
   * Agency components
   * Centralized vs non-centralized agencies
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

The main task of the FOIA Export XML module is to transform an Annual FOIA
Report Data node into an XML file that conforms to the FOIA Annual Report
extension of the NIEM IEPD schema.  A representation of this extension schema
can be found documented on
[GitHub](https://github.com/usdoj/foia-api/blob/develop/docs/FoiaAnnualReportExtensions.xsd).


### Exporting annual report nodes

The module defines a route with the pattern `/node/[nid]/xml` and a menu link
that displays on node pages.  The route's controller builds the XML from node
data and returns a response with the export contents as a file attachment for
download.


### The export class

The bulk of the work in this module is done in the class 
`\Drupal\foia_export_xml\ExportXml`.  This class builds an XML document from
 node data that conforms to the FOIA Annual Report schema extension.  The
`__toString()` method is used to convert the XML document to a string.  In
this method, a new export can be created, then cast to a string as the body
of the response object.


### Export structure

The export schema is structured in sections.  Within the `ExportXml` class, a
method exists for exporting each section.  The resulting sections, at a high
level, will look something like the following:

```
<foia:Exemption3StatuteSection>
    <foia:ReliedUponStatute s:id="ES2">...</foia:ReliedUponStatute>
    ...
    <foia:ReliedUponStatuteOrganizationAssociation>...</foia:ReliedUponStatuteOrganizationAssociation>
</foia:Exemption3StatuteSection>
```  

In this example, the elements are*:

 * `Exemption3StatuteSection`: A single section of the report.
 * `ReliedUponStatute`: A single component data element, which can contain
  multiple types of data.
 * `ReliedUponStatuteOrganizationAssociation`: An organization association
which maps component data to a specific organization within the reporting
agency.

\* This is not a complete example.  For exact specifications, reference the
[FOIA Annual Report](https://github.com/usdoj/foia-api/blob/develop/docs/FoiaAnnualReportExtensions.xsd)
schema.

  
### Agency components

Agencies can consist of one or more subunits, referred to as components. These
exist in Drupal as Agency Component nodes.  When creating an Annual FOIA
Report Data node, paragraph items containing component data will reference
one of the Agency's components, associating the data in that paragraph item
with a specific organizational subunit.

In some cases, an agency will not have multiple subunits.  In these cases the
paragraph data will reference the agency itself.  See the "Centralized vs
non-centralized agencies" section of this readme for more information.


#### Organizations

In the export, this exists by defining each `OrganizationSubUnit` (Agency
Component node), along with a reference id, such as `ORG1`.  The following
example shows the organizations section of the export, where subunits are
defined within the agency overall (`ORG0`) and given reference id values in
the attribute `s:id`.
  
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


#### Component data

While building each section of the export, component data elements are given
ids as well. This uses a section prefix and a numeric identifier.  In the below
example, the displayed `ReliedUponStatute` section has an id of `ES2` set in
the `s:id` attribute.

The second part of this example shows an `OrganizationAssociation` element
that contains references both to the component data and the organizational
subunit. We can see that the `ES2` component data is associated with `ORG1`,
which, if continuing with our example from above, corresponds to the 
`Agricultural Marketing Service` component.

```
<foia:Exemption3StatuteSection>
    ...
    <foia:ReliedUponStatute s:id="ES2">
        <j:StatuteDescriptionText>5 U.S.C. § 574(j)</j:StatuteDescriptionText>
        <foia:ReliedUponStatuteInformationWithheldText>Dispute Resolution Communications</foia:ReliedUponStatuteInformationWithheldText>
        <nc:Case>
            <nc:CaseTitleText>N/A</nc:CaseTitleText>
        </nc:Case>
    </foia:ReliedUponStatute>
    ...
    <foia:ReliedUponStatuteOrganizationAssociation>
        <foia:ComponentDataReference s:ref="ES2"/>
        <nc:OrganizationReference s:ref="ORG1"/>
        <foia:ReliedUponStatuteQuantity>3</foia:ReliedUponStatuteQuantity>
    </foia:ReliedUponStatuteOrganizationAssociation>
    ...
</foia:Exemption3StatuteSection>
```


#### The component map

In order to properly reference component data to organizations, organization
identifiers are maintained in the `ExportXml` class's `componentMap` property.
This property maps an Agency Component node id to an organizational
identifier such as `ORG1`.  For example:

```
[
  1191 => 'ORG1'
  1136 => 'ORG2'
  7896 => 'ORG3'
]
```

The `componentMap` can then be used to retrieve an organizational id during
export based on a referenced Agency Component's node id. For example:

```
$agency_component = $component->field_agency_component->referencedEntities()[0];
$identifier = $this->componentMap[$agency_component->id()]
```


### Centralized vs non-centralized agencies

A centralized agency is one with only one component, corresponding to the
agency itself. For such agencies, agency-overall data is not exported to XML
since report since that data will be contained within component data.


REQUIREMENTS
------------

This module depends on the `annual_foia_report_data` and `agency_component`
content types.


INSTALLATION
------------

FOIA Export XML is a custom Drupal module so unlike contrib modules, the
codebase is not installed via composer. Enable as you would normally enable a
contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules#s-step-2-enable-the-module
for further information.


CONFIGURATION
-------------

Any user with permission to view an Annual FOIA Report Data node, also has
permission to export that node to XML.

Configure the user permissions in Administration » People » Permissions:

 * View published content
 * View unpublished content
