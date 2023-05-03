@agencyendpoints @api
Feature: Agency API Endpoints
  I should be able to test the Agency API Endpoints listed on the swagger page
  So that I can make sure there is at least one Agency Component that is returned

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | Federal Testing Agency  | FTA                       | description |plain_text| en       |
    Given agency_component content:
      | title                   | field_agency              | field_rep_start | field_agency_comp_abbreviation |
      | Test Agency Component 1 | Federal Testing Agency    | 2019-01-01      | ABCDEF                         |


  @api @agency
  Scenario: Agency Component API Endpoint Works
    Given I request "/api/agency_components"
    Then the response code is 200
    And the response body contains JSON:
        """
        {
            "data": []
        }
        """

  @api @agencytaxonomy
  Scenario: Agency Taxonomy API Endpoint Works
    Given I request "/api/agency"
    And I wait 2 seconds
    Then the response code is 200
    And the response body contains JSON:
        """
        {
            "data": []
        }
        """
