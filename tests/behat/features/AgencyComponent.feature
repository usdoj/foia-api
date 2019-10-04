@agency_component @experimental
Feature: Agency Component Feature
  In order to handle FOIA requests properly
  As an Agency Component staff member
  I should be able to specify data and relationships specific to my component

  @api
  Scenario: Confirm existence of Agency Component fields
    Given "agency" terms:
      | name  |field_agency_abbreviation| description |format    | language |
      | test  |DOJ                      | description |plain_text| en       |
    When I am logged in as a user with the 'Administrator' role
    And I create a webform "a_test_webform_1"
    And agency_component content:
      | title                   | field_agency | field_agency_comp_abbreviation | field_agency_comp_telephone | Request Submission Form | field_request_data_year | field_complex_average_days | field_complex_highest_days | field_complex_lowest_days | field_complex_median_days | field_expedited_average_days | field_expedited_highest_days | field_expedited_lowest_days | field_expedited_median_days | field_simple_average_days | field_simple_highest_days | field_simple_lowest_days | field_simple_median_days |
      | A Test Agency Component | test         | TST                          | (555) 555-5555              | a_test_webform          | 2016                    | 1                          | 1                          | Less than 1               | 1                         | 3.14                         | 0                            | 3                           | 9                           | 1                         | 6                         | 1                        | 1                        |
    And I am at 'admin/content'
    And for 'Title' I enter 'A Test Agency Component'
    And I press the 'Filter' button
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
