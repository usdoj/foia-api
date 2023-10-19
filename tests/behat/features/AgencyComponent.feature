@agency_component
Feature: Agency Component Feature
  In order to create Agency Component
  As an Administrator
  I should be able to create and edit an Agency Component entity.

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | Testing Agency          | FTA                       | description |plain_text| en       |
    Given agency_component content:
      | title                   | field_agency              | field_rep_start | field_agency_comp_abbreviation |
      | Test Agency Component 1 | Testing Agency            | 2019-01-01      | ABCDEF                         |
    Given users:
      | name   | mail              | roles                | field_agency   |
      | Mini   | mini@example.com  | Administrator        | Testing Agency |
      | Angus  | angus@example.com | Agency Administrator | Testing Agency |
      | Agency | angus@example.com | Agency Manager       | Testing Agency |

  @api
  Scenario: Agency Component name in title tag for Agency Component node.
    Given I am logged in as a user with the 'Administrator' role
    And I am at '/node/add/agency_component'
    And for 'Agency Component Name' I enter 'My agency name'
    And for 'Agency' I enter 'Federal Testing Agency'
    And for 'Abbreviation' I enter 'FTA'
    And for 'Street address' I enter 'testing'
    And for 'City' I enter 'Rockville'
    And for 'Zip code' I enter "20857'
    And I select "Maryland" from "State"
    And I select "Email" from "Portal Submission Format"
    And for 'Submission Email' I enter 'test@test.com'
    When I press the 'Save' button
    Then the page title should be "My agency name | Federal Testing Agency | National FOIA Portal"

  @api @agency
  Scenario: Agency Manager can not edit agency compnent title
    Given I am logged in as a user with the 'Agency Manager' role
    And I wait 5 seconds
    And I am at 'user'
    And I wait 5 seconds
    And I should see "Testing Agency"
    And I click 'Testing Agency'
