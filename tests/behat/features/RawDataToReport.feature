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
