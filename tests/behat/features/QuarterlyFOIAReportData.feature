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

