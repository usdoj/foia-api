@agency_component
Feature: Agency Component Feature
  In order to create Agency Component
  As an Administrator
  I should be able to create and edit an Agency Component entity.

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | Federal Testing Agency  | FTA                       | description |plain_text| en       |

  @api
  Scenario: Agency Component name in title tag for Agency Component node.
    Given I am logged in as a user with the 'Administrator' role
    And I am at '/node/add/agency_component'
    And for 'Agency Component Name' I enter 'My agency name'
    And for 'Agency' I enter 'Federal Testing Agency'
    And for 'Abbreviation' I enter 'ABC'
    When I press the 'Save' button
    Then I should see "<title>My agency name | Federal Testing Agency | National FOIA Portal</title>"
