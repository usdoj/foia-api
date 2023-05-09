#@reportdata @api
#Feature: Annual and Quarterly Report API test
#  I should be able to test the Agency Taxonomy Endpoints
#  So that I can make sure there is at least one Agency Component that is returned
#
#  Background:
#    Given agency terms:
#      | name                    | field_agency_abbreviation | description |format    | language |
#      | Department of Justice  | doj                       | description |plain_text| en       |
#    Given agency_component content:
#      | title                   | field_agency              | field_rep_start | field_agency_comp_abbreviation |
#      | Department of Justice test | Department of Justice   | 2021-01-01      | doj   |
#    Given quarterly_foia_report_data content:
#      | title                   | field_agency              | field_quarterly_year | field_quarterly_quarter |
#      | Quarter | Department of Justice   | 2019     | 1   |
#      | Quarter2 | Department of Justice   | 2020     | 1   |
#      | Quarter3 | Department of Justice   | 2021     | 1   |
