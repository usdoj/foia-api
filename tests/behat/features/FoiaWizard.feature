@foia_wizard
Feature: FOIA Wizard Feature
  In order to view to FOIA Wizard Endpoints
  I should be able to view FOIA Wizard Endpoints

  @api @foia_wizard_settings
  Scenario: Check Trigger Phrases JSON file for response code 200
    Given I request "/modules/custom/foia_wizard/trigger-phrases.json"
    Then the response code is 200
    Then the "Content-Type" response header exists
