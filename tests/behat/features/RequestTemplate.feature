@template
Feature: Request template Feature
  In order to create a form appropriate for each agency component
  As an agency staff member
  I should be able to duplicate the universal form template with common fields

  @api
  Scenario: Ensure common universal fields in form template
    Given I am logged in as a user with the 'Administer webforms' permission
    And I am at 'form/basic-request-submission-form'
    Then I should see a 'Prefix/Title' field
    And I should see a 'First Name' field
    And I should see a 'Middle Initial/Middle Name' field
    And I should see a 'Last Name' field
    And I should see a 'Suffix' field
    And I should see a 'Company/Organization' field
    And I should see a 'Email' field
    And I should see a 'Phone Number' field
    And I should see a 'Fax Number' field
    And I should see a 'Mailing Address Line 1' field
    And I should see a 'Mailing Address Line 2' field
    And I should see a 'City' field
    And I should see a 'Country' field
    And I should see a 'State/Province' field
    And I should see a 'Zip/Postal Code' field
    And I should see a 'Processing Fees' field
    And I should see a 'Delivery Method' field
    And I should see a 'Request Category' field
    And I should see a "Describe the information you're requesting" field
    And I should see a 'Request Fee Waiver' field
    And I should see a 'Request Expedited Processing' field
    And I should see a 'Attachments/Supporting Documentation' field
    And I should see a 'I agree to the privacy policy' field
    When I am at 'webform/basic_request_submission_form/test'
    And I press the 'Submit' button
    Then I should be on "form/basic-request-submission-form/confirmation"
