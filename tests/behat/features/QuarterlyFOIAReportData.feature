@quarterly_foia_report_data
Feature: Quarterly FOIA Report Data Feature
  In order to create Quarterly FOIA Reports
  As an Administrator
  I should be able to create and edit an Quarterly FOIA Report Data entity.

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | Federal Testing Agency  | FTA                       | description |plain_text| en       |
    Given agency_component content:
      | title                   | field_agency              | field_rep_start | field_agency_comp_abbreviation |
      | Test Agency Component 1 | Federal Testing Agency    | 2019-01-01      | ABCDEF                         |

  @api @javascript
  Scenario: The Components should be required for Quarterly reports
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/quarterly_foia_report_data"
    And I select "Federal Testing Agency" from "Agency"
    And I wait 5 seconds
    When I press the 'Save and continue' button
    Then I should see "Components field is required"

  @api @javascript
  Scenario: There is a button for adding placeholders for component data
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/quarterly_foia_report_data"
    And I select "Federal Testing Agency" from "Agency"
    And I wait 5 seconds
    And I check the box "ABCDEF"
    And for 'Fiscal Year' I enter '2024'
    And I select "Q1" from "Quarter"
    And I press the 'Save and continue' button
    And I click 'Component data'
    Then I should see "Add placeholders for component data below"

  @api @javascript
  Scenario: Quarterly reports - handle case where user removes component data
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/quarterly_foia_report_data"
    And I select "Federal Testing Agency" from "Agency"
    And I wait 5 seconds
    And I check the box "ABCDEF"
    And for 'Fiscal Year' I enter '2024'
    And I select "Q1" from "Quarter"
    And I press the 'Save and continue' button
    And I click 'Component data'
    And I wait 5 seconds
    And I select "ABCDEF" from "Agency/Component"
    And I wait 5 seconds
    And for 'Number of requests received' I enter '123'
    And for 'Number of requests processed' I enter '23'
    And for 'Number of requests backlogged' I enter '3'
    And I click 'Agency Overall'
    And I wait 3 seconds
    Then the "Agency Overall - Number of requests received" element should have the value "123"
    Then the "Agency Overall - Number of requests processed" element should have the value "23"
    Then the "Agency Overall - Number of requests backlogged" element should have the value "3"
    And I click 'Component data'
    And I wait 3 seconds
    And I press the 'Remove' button
    And I wait 5 seconds
    And I press the 'Confirm removal' button
    And I wait 5 seconds
    And I click 'Agency Overall'
    And I wait 3 seconds
    Then the "Agency Overall - Number of requests received" element should have the value "0"
    Then the "Agency Overall - Number of requests processed" element should have the value "0"
    Then the "Agency Overall - Number of requests backlogged" element should have the value "0"
