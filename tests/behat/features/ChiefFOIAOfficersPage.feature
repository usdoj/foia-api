@cfo @cfopage @api
Feature: Chief FOIA Officers Page API
  I should be able to get data from the CFO Page Endpoints

  Background:
    Given cfo_page content:
      | title             | body             | moderation_state |
      | CFO Page title | CFO Page body | published        |

  @api
  Scenario: CFO Page API Works
    Given I request "/api/node/cfo_page"
    Then the response code is 200
    Then the "Content-Type" response header exists
    Then the response body contains JSON:
        """
    {
    	"data": [{
    		"type": "node--cfo_page"
    	}]
    }
        """
