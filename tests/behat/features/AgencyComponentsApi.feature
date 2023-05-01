@agencycomp @api
Feature: Agency API Endpoints
  Test each API end point and make sure they are all returning data without errors
  I should be able to test the Agency API Endpoints listed on the swagger page

  Background:
    Given agency_component content:
      | title             | agency_comp_abbreviation | moderation_state |
      | Veterans Experience Office |  veo | published  |

  @api @agency
  Scenario: Agency Component API Endpoint Works
#    Given I request "/api/agency_components" using HTTP "GET"
    Given I request "/api/agency_components"
    Then the response code is 200
#    And the response body contains JSON:
#        """
#        {
#            "jsonapi":"agency_component" &include=agency
#        }
#        """
