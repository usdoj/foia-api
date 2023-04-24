@agency_component @annual_foia_report_data
Feature: Processing Data Time
  In order to create Agency Component and Annual Report
  As an Administrator I should be able to change field(Simple Median Days)
  value in annual report to reflects on the same field of Agency component.

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | appraisal subcommitte   | ASC                       | description |plain_text| en       |
    Given agency_component content:
      | title                   | field_agency              | field_rep_start | field_agency_comp_abbreviation |
      | Test Agency Component 1 | appraisal subcommitte     | 2023-01-01      | ASC                            |

  @api @javascript
  Scenario: There is check box "Require manual entry of processing times" for Agency Component node.
# Create testing Agency Component with URL alias: /test-component.
    Given I am logged in as a user with the "Administrator" role
    And I am at "/node/add/agency_component"
    Then the "Require manual entry of processing times" element should exists
    And I wait 5 seconds
    And for 'Agency Component Name' I enter 'appraisal subcommitte'
    And for 'Agency' I enter 'appraisal subcommitte'
    And I wait 3 seconds
    And for 'Abbreviation' I enter 'ASC'
    And I check the box "Is Centralized"
    Then I expand the "URL ALIAS (No alias)"
    And for 'URL alias' I enter '/test-component'
    And I wait 3 seconds
    And for 'Street address' I enter '123 testing Address'
    And for 'City' I enter 'Rockville'
    And for 'Zip code' I enter '0857'
    And I select "Maryland" from "State"
    And I select "Email" from "Portal Submission Format"
    And for 'Submission Email' I enter 'test@test.com'
    And for 'Request Data Year' I enter '2023'
    And for 'Complex Average Days' I enter '122.2'
    And for 'Complex Highest Days' I enter '233'
    And for 'Complex Lowest Days' I enter '47'
    And for 'Complex Median Days' I enter '99'
    And for 'Expedited Average Days' I enter 'N/A'
    And for 'Expedited Highest Days' I enter 'N/A'
    And for 'Expedited Lowest Days' I enter 'N/A'
    And for 'Expedited Median Days' I enter 'N/A'
    And for 'Simple Average Days' I enter '103.4'
    And for 'Simple Highest Days' I enter '200'
    And for 'Simple Lowest Days' I enter '43'
# The following field will be used to chech testing result.
    And for 'Simple Median Days' I enter '106'
    Then I expand the "ANNUAL FOIA REPORT START/EXPIRATION DATES"
    And for 'Report Start Date' I enter '04/19/2023'
    And I wait 3 seconds
    # And for 'Report Start Date' I enter '04/19/2023'
    And I press the "Save" button
    And I wait 5 seconds
    And I click 'Edit'
    And I wait 5 seconds
    Then the element "Request Data Year" is "disabled"
    Then the element "Complex Average Days" is "disabled"
    Then the element "Complex Highest Days" is "disabled"
    Then the element "Complex Lowest Days" is "disabled"
    Then the element "Complex Median Days" is "disabled"
    Then the element "Expedited Average Days" is "disabled"
    Then the element "Expedited Highest Days" is "disabled"
    Then the element "Expedited Lowest Days" is "disabled"
    Then the element "Expedited Median Days" is "disabled"
    Then the element "Simple Average Days" is "disabled"
    Then the element "Simple Highest Days" is "disabled"
    Then the element "Simple Lowest Days" is "disabled"
    Then the element "Simple Median Days" is "disabled"
    And I check the box "Require manual entry of processing times"
    And I press the 'Save' button
    And I wait 5 seconds
# Here is testing when "Require manual entry of processing times" checked.
    And I click 'Edit'
    And I wait 5 seconds
    Then the element "Request Data Year" is "enabled"
    Then the element "Complex Average Days" is "enabled"
    Then the element "Complex Highest Days" is "enabled"
    Then the element "Complex Lowest Days" is "enabled"
    Then the element "Complex Median Days" is "enabled"
    Then the element "Expedited Average Days" is "enabled"
    Then the element "Expedited Highest Days" is "enabled"
    Then the element "Expedited Lowest Days" is "enabled"
    Then the element "Expedited Median Days" is "enabled"
    Then the element "Simple Average Days" is "enabled"
    Then the element "Simple Highest Days" is "enabled"
    Then the element "Simple Lowest Days" is "enabled"
    Then the element "Simple Median Days" is "enabled"
    And I uncheck the box "Require manual entry of processing times"
    And I press the 'Save' button
    And I wait 5 seconds
