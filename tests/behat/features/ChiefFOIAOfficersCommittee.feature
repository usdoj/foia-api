@cfo @cfocommittee @api
Feature: Chief FOIA Officers Committee
  I should be able to get data from the CFO Committee Endpoints

  Background:
    Given cfo_committee content:
      | title             | body             | moderation_state |
      | CFO committee title | CFO committee body | published        |

#  @api @javascript
#  Scenario: Add a CFO committee
#    Given users:
#      | name   | mail              | roles                |
#      | Mini   | mini@example.com  | Administrator        |
#      | Angus  | angus@example.com | Agency Administrator |
#    Given I am logged in as a user with the 'Administrator' role
#    And I am at '/node/add/cfo_committee'
#    And for 'Title' I enter 'Test CFO Committee'
#    And for 'URL Slug' I enter 'test-cfo-committee'
#    And for 'Body' I enter 'Anything'
#    When I press the 'Save' button
#    Then the page title should be "Test CFO Committee | National FOIA Portal"

  @api
  Scenario: CFO Committee API Works
    Given I request "/api/node/cfo_committee"
    Then the response code is 200
    Then the "Content-Type" response header exists
    Then the response body contains JSON:
        """
    {
    	"data": [{
    		"type": "node--cfo_committee"
    	}]
    }
        """
