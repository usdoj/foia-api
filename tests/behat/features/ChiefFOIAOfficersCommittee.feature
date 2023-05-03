@cfocommittee @api
Feature: Chief FOIA Officers Committee
  I should be able to get data from the CFO Committee Endpoints

  Background:
    Given cfo_committee content:
      | title             | body             | moderation_state |
      | CFO committee title | CFO committee body | published        |


  Scenario: CFO Committee API Works
    Given I request "/api/node/cfo_committee"
    Then the response code is 200
    Then the "Content-Type" response header exists
    And the response body contains JSON:
        """
        {
            "data": []
        }
        """
