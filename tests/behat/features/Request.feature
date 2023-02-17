@request
Feature: Request information from an agency
  In order to obtain information
  As an end user
  I should be able to submit a request

  Background:
    Given I am logged in as a user with the "Administrator" role
  #  When I am at "admin/modules"
  #  Then I check the box "Database Logging"
  #  And I press the "Install" button

  @api
  Scenario: Attempt to send request via email with unassociated form
    Given I am at 'admin/structure/webform/add'
    And for 'Machine-readable name' I enter 'test_webform'
    And for 'Title' I enter 'Test webform'
    And I press the 'Save' button
    And I am at 'form/test-webform'
    And for "First name" I enter "Ringo"
    And for 'Last name' I enter 'Star'
    And for 'Email' I enter 'test@example.com'
    And for "Your request" I enter 'Test description'
    And I select "No" from "Fee waiver"
    And I select "No" from "Expedited processing"
    And I press the 'Submit' button
#    When I am at 'admin/reports/dblog'
#    Then I should see the text 'Unassociated form: The form, Test webform'

  @api
  Scenario: Attempt to send request with no associated email
    Given I am at 'admin/modules'
    And I check the box 'Maillog / Mail Developer'
    And I press the 'Install' button
    And the cache has been cleared
    And I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'Test agency component'
    And I select "Test webform" from "Request Submission Form"
    And I press the 'Save' button
    And I am at 'form/test-webform'
    And for "First name" I enter "Ringo"
    And for 'Last name' I enter 'Star'
    And for 'Email' I enter 'test@example.com'
    And for "Your request" I enter 'Test description'
    And I select "No" from "Fee waiver"
    And I select "No" from "Expedited processing"
    And I press the 'Submit' button
#    When I am at 'admin/reports/dblog'
#    Then I should see the text 'No Submission Email: Unable to send email for Test…'

  @api @experimental
  Scenario: Send request email
    Given I am at 'admin/modules'
    And I check the box 'Maillog / Mail Developer'
    And I press the 'Install' button
    And the cache has been cleared
    And I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'Test agency component'
    And I select "Test webform" from "Request Submission Form"
    And for 'Submission Email' I enter 'test@example.com'
    When I press the 'Save' button
    And save the current URL
    Then I should see the following success messages:
      | Agency Component Test agency component has been created. |
    And I am at 'form/test-webform'
    And for "First name" I enter "Ringo"
    And for 'Last name' I enter 'Star'
    And for 'Email' I enter 'test@example.com'
    And for "Your request" I enter 'Test description'
    And I select "No" from "Fee waiver"
    And I select "No" from "Expedited processing"
    When I press the 'Submit' button
#    Then I should see the text 'Ringo'
#    And I should see the text 'Star'
#    And I should see the text 'test@example.com'
#    And I should see the text 'United States'
#    And I should see the text 'Test'
#    When I am at 'admin/reports/dblog'
#    Then I should see the text 'Test webform webform sent FOIA Email email.'
    Given I am an anonymous user
    When I go to saved URL
    Then I should see the link 'Test webform'
