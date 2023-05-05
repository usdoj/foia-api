@reportdata @api
Feature: Annual and Quarterly Report API test
  I should be able to test the Agency Taxonomy Endpoints
  So that I can make sure there is at least one Agency Component that is returned

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | Department of Justice  | DOJ                       | description |plain_text| en       |
    Given agency_component content:
      | title                   | field_agency              | field_rep_start | field_agency_comp_abbreviation |
      | Department of Justice test | Department of Justice   | 2019-01-01      | DOJ                         |

  @api @annual
  Scenario: Annual Reports API
    Given I request "/api/annual_foia_report?page[limit]=1"
    And I wait 10 seconds
    Then the response code is 200
    And the response body contains JSON:
        """
        {
            "data": []
        }
        """
  @api @annualfiscal
  Scenario: Fiscal Years for Annual Reports
    Given I request "/api/annual_foia_report/fiscal_years"
    And I wait 1 seconds
    Then the response code is 200
    Then the response body is a JSON array with a length of at least 4

  @api @annualxml
  Scenario: Annual Reports XML API
    Given I request "/api/annual-report-xml/doj/2020"
    And I wait 1 seconds
    Then the response code is 200
#    And the request body contains "FOIA Annual Report"
    Then the "Content-Type" response header exists
    Then the "Content-Type" response header matches "/(text\/html|charset=UTF-8)/i"

  @api @quarterly
  Scenario: Quarterly Report Data API
    Given I request "/api/quarterly_foia_report?page[limit]=1"
    And I wait 1 seconds
    Then the response code is 200
    Then the "Content-Type" response header exists
    And the response body contains JSON:
        """
        {
            "data": []
        }
        """
  @api @quarterlyfiscal
  Scenario: Quarterly Report Fiscal Year API
    Given I request "/api/quarterly_foia_report/fiscal_years"
    And I wait 1 seconds
    Then the response code is 200
    Then the "Content-Type" response header exists
    And the response body contains JSON:
        """
[
    "2022",
    "2021"
]
        """
