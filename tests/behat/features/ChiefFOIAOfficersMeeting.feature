@cfomeeting @api
Feature: Chief FOIA Officers Meeting
  I should be able to get data from the CFO Meeting Endpoints

  Background:
    Given cfo_meeting content:
      | title             | body             | moderation_state |
      | CFO Meeting title | CFO Meeting body | published        |


  Scenario: CFO Meeting API Works
    Given I request "/api/node/cfo_meeting"
    Then the response code is 200
    Then the "Content-Type" response header exists
    And the response body contains JSON:
        """
        {
            "data": []
        }
        """
