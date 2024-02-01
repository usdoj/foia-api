@configuration
Feature: General site wide configuration
  In order to comply with security and performance standards
  As an Administrator
  I should be able to verify that my site configuration remains how it is intended to be

  @api
  Scenario: Modules installed or uninstalled
    Given I am logged in as a user with the "Administrator" role
    When I am at "admin/modules"
    Then the "Syslog" checkbox should be checked
    And the "Contact" checkbox should not be checked
    And the "Contact Form" checkbox should not be checked
    And the "Migrate" checkbox should be checked
    And the "Migrate Plus" checkbox should be checked
    And the "Migrate Tools" checkbox should be checked
    And the "FOIA Webform" checkbox should be checked

  @api
  Scenario: Only administrators should see error messages
    Given I am logged in as a user with the "Administrator" role
    When I am at "admin/config/development/logging"
    Then I should see the radio button "None" selected
