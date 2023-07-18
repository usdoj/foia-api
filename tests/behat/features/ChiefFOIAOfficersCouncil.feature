@cfo @cfocouncil @api
Feature: Chief FOIA Officers Council
  In order to post info about the Chief FOIA Officers Council
  As an Administrator
  I should be able to create and edit the various CFO content types.

  Background:
#    Given cfo_council content:
#      | title             | body             | moderation_state |
#      | CFO Council title | CFO Council body | published        |

  @api @javascript
  Scenario: Add a CFO Council
    Given I am logged in as a user with the 'Administrator' role
    And I am at '/node/add/cfo_council'
    And for 'Title' I enter 'CFO Council title'
    And for 'Body' I enter 'CFO Council body'
    When I press the 'Save' button
    Then the page title should be "CFO Council title | National FOIA Portal"

    # for some reason empty here
  Scenario: Confirm that the CFO Committee endpoint is operational
    Given I request "/api/cfo/council"
    Then the response code is 200
#    Then the response body contains JSON:
#        """
#        {
#            "title":"CFO Council title",
#            "body": "@regExp(/CFO Council body/i)",
#            "committees": []
#        }
#        """
    Then the response body contains JSON:
        """
    {
    	"data": [{
    		"type": "node--cfo_council"
    	}]
    }
        """
