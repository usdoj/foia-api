@cfo @api
Feature: Chief FOIA Officers Council
  In order to post info about the Chief FOIA Officers Council
  As an Administrator
  I should be able to create and edit the various CFO content types.

  Background:
    Given cfo_council content:
      | title             | body             | moderation_state |
      | CFO Council title | CFO Council body | published        |

  Scenario: Committee endpoint works
    Given I request "/api/cfo/council"
    Then the response code is 200
    And the response body contains JSON:
        """
        {
            "title":"CFO Council title",
            "body": "@regExp(/CFO Council body/i)",
            "committees": []
        }
        """
