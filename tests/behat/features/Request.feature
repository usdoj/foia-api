@request
Feature: Request information from an agency
  In order to obtain information
  As an end user
  I should be able to submit a request

  @api
  Scenario: Attempt to send request via email with unassociated form
    Given I am logged in as a user with the 'Administrator' role
    And I am at 'admin/structure/webform/add'
    And for 'Machine-readable name' I enter 'test_webform'
    And for 'Title' I enter 'Test webform'
    And I press the 'Save' button
    And I am at 'form/test-webform'
    And for "First Name" I enter "Ringo"
    And for 'Last name' I enter 'Star'
    And for 'Email' I enter 'test@example.com'
    And for "Describe the information you're requesting" I enter 'Test description'
    And I select "No" from "Request Fee Waiver"
    And I select "No" from "Request Expedited Processing"
    And I press the 'Submit' button
    When I am at 'admin/reports/dblog'
#    Then I should see the text 'Unassociated form: The form, Test webform'

  @api
  Scenario: Attempt to send request with no associated email
    Given I am logged in as a user with the 'Administrator' role
    And I am at 'admin/modules'
    And I check the box 'Maillog / Mail Developer'
    And I press the 'Install' button
    And I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'Test agency component'
    And I select "Test webform" from "Request Submission Form"
    And I press the 'Save and publish' button
    And I am at 'form/test-webform'
    And for "First Name" I enter "Ringo"
    And for 'Last name' I enter 'Star'
    And for 'Email' I enter 'test@example.com'
    And for "Describe the information you're requesting" I enter 'Test description'
    And I select "No" from "Request Fee Waiver"
    And I select "No" from "Request Expedited Processing"
    And I press the 'Submit' button
    When I am at 'admin/reports/dblog'
#    Then I should see the text 'No Submission Email: Unable to send email for Test…'

  @api
  Scenario: Send request email
    Given I am logged in as a user with the 'Administrator' role
    And I am at 'admin/modules'
    And I check the box 'Maillog / Mail Developer'
    And I press the 'Install' button
    And I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'Test agency component'
    And I select "Test webform" from "Request Submission Form"
    And for 'Submission Email' I enter 'test@example.com'
    When I press the 'Save and publish' button
    Then I should see the following success messages:
      | Agency Component Test agency component has been created. |
    And I am at 'form/test-webform'
    And for "First Name" I enter "Ringo"
    And for 'Last name' I enter 'Star'
    And for 'Email' I enter 'test@example.com'
    And for "Describe the information you're requesting" I enter 'Test description'
    And I select "No" from "Request Fee Waiver"
    And I select "No" from "Request Expedited Processing"
    When I press the 'Submit' button
#    Then I should see the text 'Ringo'
#    And I should see the text 'Star'
#    And I should see the text 'test@example.com'
#    And I should see the text 'United States'
#    And I should see the text 'Test'
    When I am at 'admin/reports/dblog'
#    Then I should see the text 'Test webform webform sent FOIA Email email.'
