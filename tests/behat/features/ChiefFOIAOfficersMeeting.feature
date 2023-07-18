@cfo @cfomeeting @api
Feature: Chief FOIA Officers Meeting
  I should be able to get data from the CFO Meeting Endpoints

  Background:
    Given cfo_meeting content:
      | title             | body             | moderation_state |
      | CFO Meeting title | CFO Meeting body | published        |

  @api @javascript
  Scenario: Add a CFO Meeting
    Given I am logged in as a user with the 'Administrator' role
    And I am at '/node/add/cfo_meeting'
    And for 'Title' I enter 'CFO Meeting title'
    And for 'Body' I enter 'CFO Meeting body'
    When I press the 'Save' button
    Then the page title should be "CFO Meeting title | National FOIA Portal"


  @api
  Scenario: CFO Meeting API Works
    Given I request "/api/node/cfo_meeting"
    Then the response code is 200
    Then the "Content-Type" response header exists
    Then the response body contains JSON:
        """
    {
    	"data": [{
    		"type": "node--cfo_meeting"
    	}]
    }
        """
