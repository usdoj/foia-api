@agency_component
Feature: Agency Component Feature
  In order to handle FOIA requests properly
  As an Agency Component staff member
  I should be able to specify data and relationships specific to my component

  @api
  Scenario: Confirm existence of Phone and Abbreviation fields
    Given I am logged in as a user with the 'Administrator' role
    When I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'A Test Agency Component'
    And for 'Abbreviation' I enter 'TEST'
    And for 'Telephone' I enter '(555) 555-5555'
    And for 'Request Submission Form' I enter 'basic_request_submission_form'
    And for 'Request Data Year' I enter '2016'
    And for 'Complex Average Days' I enter '1'
    And for 'Complex Highest Days' I enter '1'
    And for 'Complex Lowest Days' I enter 'less than 1'
    And for 'Complex Median Days' I enter '1'
    And for 'Expedited Average Days' I enter '3.14'
    And for 'Expedited Highest Days' I enter '0'
    And for 'Expedited Lowest Days' I enter '3'
    And for 'Expedited Median Days' I enter '9'
    And for 'Simple Average Days' I enter '1'
    And for 'Simple Highest Days' I enter '6'
    And for 'Simple Lowest Days' I enter '1'
    And for 'Simple Median Days' I enter '1'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component A Test Agency Component has been created. |
    And I click 'A Test Agency Component'
    And save the current URL
    When I am logged in as a user with the 'Agency Manager' role
    And I go to saved URL
    Then I should see the text 'TEST'
    And I should see the link '(555) 555-5555'
    And I should see the text 'Request Data Year'
    And I should see the text 'Complex Average Days'
    And I should see the text 'Complex Highest Days'
    And I should see the text 'Complex Lowest Days'
    And I should see the text 'Complex Median Days'
    And I should see the text 'Expedited Average Days'
    And I should see the text 'Expedited Highest Days'
    And I should see the text 'Expedited Lowest Days'
    And I should see the text 'Expedited Median Days'
    And I should see the text 'Simple Average Days'
    And I should see the text 'Simple Highest Days'
    And I should see the text 'Simple Lowest Days'
    And I should see the text 'Simple Median Days'
    And I should see the text '2016'
    And I should see the text '0'
    And I should see the text 'less than 1'
    And I should see the text '1'
    And I should see the text '3'
    And I should see the text '3.14'
    And I should see the text '6'
    And I should see the text '9'
