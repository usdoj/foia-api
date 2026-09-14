@api @raw_data_to_report
Feature: Raw data XML report action
  Agency users have an entry point for generating XML reports.

  Scenario: An administrator can invoke the placeholder
    Given I am logged in as a user with the "Administrator" role
    When I am viewing a "raw_data_to_report" with the title "Raw data action fixture"
    Then I should see the button "Generate XML Report"
    When I press "Generate XML Report"
    Then I should see "XML report generation is not implemented yet."

  Scenario: Anonymous visitors cannot generate reports
    Given I am an anonymous user
    When I am viewing a "raw_data_to_report" with the title "Raw data action fixture"
    Then I should not see the button "Generate XML Report"
