@agency_component @annual_foia_report_data
Feature: Processing Data Time
  In order to create Agency Component and Annual Report
  As an Administrator I should be able to change field(Simple Median Days)
  value in annual report to reflects on the same field of Agency component.

  Background:
    Given I am logged in as a user with the "Administrator" role
    And agency terms:
      | name        | field_agency_abbreviation |
      | Test Agency | TESTAGENCY                |
    And agency_component content:
      | title                 | field_agency | field_rep_start | field_agency_comp_abbreviation | path_alias             |
      | Test Agency Component | Test Agency  | 2019-01-01      | TESTAGENCYCOMPONENT            | /test-agency-component |

  @api @javascript
  Scenario: Processing time data are automatically populated when reports are published
    Given I am at "/test-agency-component"
    Then I should see "Test Agency Component"
    And I should not see "123456789"
    And I am at "node/add/annual_foia_report_data"
    And I select "Test Agency" from "Agency"
    And I wait 5 seconds
    And for 'FOIA Annual Report Year' I enter '2023'
    And I check the box "TESTAGENCYCOMPONENT"
    When I press the 'Save and continue' button
    And I ignore the admin menu
    And I click the section 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS'
    And I click 'Simple' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And I select "TESTAGENCYCOMPONENT" from "Agency/Component" in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And for 'Median Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '123456789'
    And for 'Average Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '123456789'
    And I select "Draft" from "Change to"
    And I press the save button at the bottom of the page
    And I click the edit tab
    And I select "Submitted to OIP" from "Change to"
    And I press the save button at the bottom of the page
    And I click the edit tab
    And I select "Cleared" from "Change to"
    And I press the save button at the bottom of the page
    And I click the edit tab
    And I select "Published" from "Change to"
    And I press the save button at the bottom of the page
    And I am at "/test-agency-component"
    Then I should see "Test Agency Component"
    And I should see "123456789"

  @api @javascript
  Scenario: The processing time fields can be manually enabled to prevent automatic population
    Given I am at "/test-agency-component"
    And I click the edit tab
    And I expand the "Annual FOIA Report Start/Expiration Dates"
    And I expand the "FOIA Portal Interoperability"
    Then the element "Request Data Year" is "disabled"
    And the element "Complex Average Days" is "disabled"
    And the element "Complex Highest Days" is "disabled"
    And the element "Complex Lowest Days" is "disabled"
    And the element "Complex Median Days" is "disabled"
    And the element "Expedited Average Days" is "disabled"
    And the element "Expedited Highest Days" is "disabled"
    And the element "Expedited Lowest Days" is "disabled"
    And the element "Expedited Median Days" is "disabled"
    And the element "Simple Average Days" is "disabled"
    And the element "Simple Highest Days" is "disabled"
    And the element "Simple Lowest Days" is "disabled"
    And the element "Simple Median Days" is "disabled"
    And I check the box "Require manual entry of processing times"
    And for 'Street address' I enter '123 testing Address'
    And for 'City' I enter 'Rockville'
    And for 'Zip code' I enter '20857'
    And I select "Maryland" from "State"
    And I select "Email" from "Portal Submission Format"
    And for 'Submission Email' I enter 'test@test.com'
    And I press the save button at the bottom of the page
    And I click the edit tab
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
    And for "Simple Average Days" I enter "987654321"
    And I press the save button at the bottom of the page
    And I am at "node/add/annual_foia_report_data"
    And I select "Test Agency" from "Agency"
    And I wait 5 seconds
    And for 'FOIA Annual Report Year' I enter '2023'
    And I check the box "TESTAGENCYCOMPONENT"
    When I press the 'Save and continue' button
    And I ignore the admin menu
    And I click the section 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS'
    And I click 'Simple' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And I select "TESTAGENCYCOMPONENT" from "Agency/Component" in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And for 'Median Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '123456789'
    And for 'Average Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '123456789'
    And I select "Draft" from "Change to"
    And I press the save button at the bottom of the page
    And I click the edit tab
    And I select "Submitted to OIP" from "Change to"
    And I press the save button at the bottom of the page
    And I click the edit tab
    And I select "Cleared" from "Change to"
    And I press the save button at the bottom of the page
    And I click the edit tab
    And I select "Published" from "Change to"
    And I press the save button at the bottom of the page
    And I am at "/test-agency-component"
    Then I should see "Test Agency Component"
    And I should not see "123456789"
    And I should see "987654321"

  @api @javascript
  Scenario: Edits of past annual reports should not update processing times on component pages
    Given I am at "/test-agency-component"
    Then I should see "Test Agency Component"
    And I should not see "123456789"
    And I click the edit tab
    And I check the box "Require manual entry of processing times"
    And I press the save button at the bottom of the page
    And I wait 5 seconds
    And I click the edit tab
    And for 'Request Data Year' I enter '2023'
    And I uncheck the box "Require manual entry of processing times"
    And I press the save button at the bottom of the page
    And I wait 5 seconds
    And I am at "node/add/annual_foia_report_data"
    And I select "Test Agency" from "Agency"
    And I wait 5 seconds
    And for 'FOIA Annual Report Year' I enter '2022'
    And I check the box "TESTAGENCYCOMPONENT"
    When I press the 'Save and continue' button
    And I ignore the admin menu
    And I click the section 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS'
    And I click 'Simple' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And I select "TESTAGENCYCOMPONENT" from "Agency/Component" in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And for 'Median Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '123456789'
    And for 'Average Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '123456789'
    And I select "Draft" from "Change to"
    And I press the save button at the bottom of the page
    And I click the edit tab
    And I select "Submitted to OIP" from "Change to"
    And I press the save button at the bottom of the page
    And I click the edit tab
    And I select "Cleared" from "Change to"
    And I press the save button at the bottom of the page
    And I click the edit tab
    And I select "Published" from "Change to"
    And I press the save button at the bottom of the page
    And I am at "/test-agency-component"
    Then I should see "Test Agency Component"
    And I should not see "123456789"
