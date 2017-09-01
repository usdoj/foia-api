@agency_component
Feature: Agency Component Feature
  In order to handle FOIA requests properly
  As an Agency Component staff member
  I should be able to specify data and relationships specific to my component

  @api
  Scenario: Confirm existence of Phone and Abbreviation fields
    Given I am logged in as a user with the 'Agency Component creator' role
    When I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'Test Agency Component'
    And for 'Abbreviation' I enter 'TEST'
    And for 'Telephone' I enter '(555) 555-5555'
    And for 'Request Submission Form' I enter 'basic_request_submission_form'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component Test Agency Component has been created. |
    And I should see the text 'TEST'
    And I should see the text '(555) 555-5555'
