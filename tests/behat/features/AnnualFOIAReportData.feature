@annual_foia_report_data
Feature: Annual FOIA Report Data Feature
  In order to create Annual FOIA Reports
  As an Administrator
  I should be able to create and edit an Annual FOIA Report Data entity.

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | Federal Testing Agency  | FTA                       | description |plain_text| en       |
    Given agency_component content:
      | title                   | field_agency              |
      | Test Agency Component 1 | Federal Testing Agency    |

  @api
  Scenario: Create an Annual FOIA Report Data node.
    Given I am logged in as a user with the 'Administrator' role
    And I am at 'node/add/annual_foia_report_data'
    And for 'Agency' I enter 'Federal Testing Agency'
    And for 'FOIA Annual Report Year' I enter '2019'
    And for 'Date Prepared' I enter '08/22/2019'
    When I press the 'Save' button
    Then I should see the following success messages:
      | FTA - 2019 - Annual FOIA Report has been created. |

  @api
  Scenario: Agency Administrator can add Annual FOIA Reports
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add"
    Then I should see the link "Annual FOIA Report Data"

  @api
  Scenario: Agency Administrator can save Annual FOIA Reports in all workflow
  states
    Given "agency" terms:
      | name  |field_agency_abbreviation| description |format    | language |
      | test  |DOJ                      | description |plain_text| en       |
    When I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/annual_foia_report_data"
    And for 'Agency' I enter 'test'
    And I select "Draft" from "Save as"
    When I press the 'Save' button
    And save the current URL
    Then I should see the following success messages:
      | Annual FOIA Report Data A Test Report has been created. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Submitted to OIP" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data test from manager has been updated. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Cleared" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data test from manager has been updated. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Published" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data test from manager has been updated. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Back with Agency" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data test from manager has been updated. |
