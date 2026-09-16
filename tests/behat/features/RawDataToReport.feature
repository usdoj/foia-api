@api @raw_data_to_report
Feature: Raw data XML report action
  Agency users have an entry point for generating XML reports.

  Background:
    Given agency terms:
      | name                 | field_agency_abbreviation |
      | Raw Block Agency One | RBA1                      |
      | Raw Block Agency Two | RBA2                      |
    And users:
      | name              | pass | roles          | field_agency         |
      | raw_block_manager | test | agency_manager | Raw Block Agency One |

  Scenario: An administrator can invoke the placeholder
    Given I am logged in as a user with the "Administrator" role
    When I am viewing a "raw_data_to_report" with the title "Raw data action fixture"
    Then I should see the button "Generate XML Report"
    When I press "Generate XML Report"
    Then I should see "XML report generation has been queued."

  Scenario: Anonymous visitors cannot view raw data reports
    Given I am an anonymous user
    When I am viewing a "raw_data_to_report" with the title "Raw data action fixture"
    Then the response status code should be 403
    And I should not see the button "Generate XML Report"

  Scenario: Only the manager's agency report appears
    Given raw_data_to_report content:
      | title                | field_agency         | status |
      | Own agency raw data  | Raw Block Agency One | 1      |
      | Other agency raw data| Raw Block Agency Two | 1      |
    And I am logged in as "raw_block_manager"
    When I am on "/user"
    Then I should see the link "Own agency raw data"
    And I should not see the link "Other agency raw data"
    And I should see the link "Add new Raw Data To Report"
    When I click "Own agency raw data"
    Then I should see the button "Generate XML Report"
    And I should not see the link "Add new Raw Data To Report"

  Scenario: The add link remains visible without an agency report
    Given I am logged in as "raw_block_manager"
    When I am on "/user"
    Then I should see the link "Add new Raw Data To Report"
    When I click "Add new Raw Data To Report"
    Then the URL should match "/node/add/raw_data_to_report"

  Scenario: Other roles do not see the block
    Given I am logged in as a user with the "Administrator" role
    When I am on "/user"
    Then I should not see the link "Add new Raw Data To Report"

  Scenario: Authenticated visitors can view published raw data reports
    Given I am logged in as a user with the "authenticated" role
    When I am viewing a "raw_data_to_report" with the title "Authenticated raw data fixture"
    Then the response status code should be 200
    And I should see "Authenticated raw data fixture"

  @javascript
  Scenario: An agency report can pair a component with a CSV upload
    Given agency_component content:
      | title                    | field_agency         | status |
      | Upload Fixture Component | Raw Block Agency One | 1      |
      | Second Upload Component  | Raw Block Agency One | 1      |
    And raw_data_to_report content:
      | title                 | field_agency         | field_foia_annual_report_yr | field_agency_comp_abbreviation | status |
      | Component upload form | Raw Block Agency One | 2026                       | RBA1                          | 1      |
    And I am logged in as "raw_block_manager"
    When I visit the entity of type "node" with the title "Component upload form"
    And I click "Edit"
    And I press "Add Component CSV upload"
    And I fill in "Agency Component" with "Upload Fixture Component"
    And I press the "down" key in the "Agency Component" field
    And I wait 2 seconds
    And I press the "down" key in the "Agency Component" field
    And I press the "enter" key in the "Agency Component" field
    And I attach the file "raw-data-valid.csv" to "CSV file"
    And I wait 2 seconds
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Upload Fixture Component"
    And I should see "raw-data-valid"
    And the URL should match "/node/[0-9]+$"

    When I click "Edit"
    And I press "Add Component CSV upload"
    And I fill in "field_component_uploads[1][subform][field_agency_component][0][target_id]" with "Upload Fixture Component"
    And I attach the file "raw-data-valid.csv" to "files[field_component_uploads_1_subform_field_request_data_csv_0]"
    And I wait 2 seconds
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Each Agency Component may have only one CSV upload."
    When I fill in "field_component_uploads[1][subform][field_agency_component][0][target_id]" with "Second Upload Component"
    And I press "Save"
    Then I should see "Upload Fixture Component"
    And I should see "Second Upload Component"
    And the URL should match "/node/[0-9]+$"
