@agency_component
Feature: Agency Component Feature
  In order to create Agency Component
  As an Administrator
  I should be able to create and edit an Agency Component entity.

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | Federal Testing Agency  | FTA                       | description |plain_text| en       |

    And a file named "features/bootstrap/CustomFeatureContext.php" with:
      """
      <?php
      use Drupal\FeatureContext;

      """
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
    Then this is verify title definition "My agency name | Federal Testing Agency | National FOIA Portal"

