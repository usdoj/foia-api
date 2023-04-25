@agency_component @annual_foia_report_data
Feature: Processing Data Time
  In order to create Agency Component and Annual Report
  As an Administrator I should be able to change field(Simple Median Days)
  value in annual report to reflects on the same field of Agency component.

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | appraisal subcommitte   | ASC                       | description |plain_text| en       |
    Given agency_component content:
      | title                   | field_agency              | field_rep_start | field_agency_comp_abbreviation |
      | Test Agency Component 1 | appraisal subcommitte     | 2023-01-01      | ASC                            |

  @api @javascript
  Scenario: There is check box "Require manual entry of processing times" for Agency Component node.
# Create testing Annual Report node.
    Given I am logged in as a user with the "Administrator" role
    And I am at "node/add/annual_foia_report_data"
    And I select "appraisal subcommitte" from "Agency"
    And I wait 5 seconds
    And for 'FOIA Annual Report Year' I enter '2023'
    And for 'Date Prepared' I enter '04/19/2023'
    And I check the box "ASC"
    When I press the 'Save and continue' button
    And I wait 5 seconds

# Section:'IV. Exemption 3 Statutes'.
    And I click 'IV. Exemption 3 Statutes'
    And I wait 3 seconds
    And I click 'Status'
    And I wait 3 seconds
    And for 'Statute' I enter 'N/A'
    And for 'Type of Information Withheld' I enter 'N/A'
    And for 'Case Citation' I enter 'N/A'
    And I click 'Agency/Component'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Times Relied Upon by Agency/Component' I enter '0'

# Section:'V.A. FOIA REQUESTS -- RECEIVED, PROCESSED AND PENDING FOIA REQUESTS'.
    And I click 'V.A. FOIA REQUESTS -- RECEIVED, PROCESSED AND PENDING FOIA REQUESTS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Requests Pending as of Start of Fiscal Year' I enter '1'
    And for 'Number of Requests Received in Fiscal Year' I enter '21'
    And for 'Number of Requests Processed in Fiscal Year' I enter '16'

# Section:'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS'.
    And I click 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS'
    And I wait 3 seconds
    And I click 'Agency/Component'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Full Grants' I enter '3'
    And for 'Number of Partial Grants/Partial Denials' I enter '1'
    And for 'Number of Full Denials Based on Exemptions' I enter '0'
    And I click 'Number of Full Denials Based on Reasons Other than Exemptions'
    And I wait 3 seconds
    And for 'No Records' I enter '4'
    And for 'All Records Referred to Another Component or Agency' I enter '0'
    And for 'Request Withdrawn' I enter '2'
    And for 'Fee-Related Reason' I enter '0'
    And for 'Records Not Reasonably Described' I enter '0'
    And for 'Improper FOIA Request for Other Reason' I enter '6'
    And for 'Not Agency Record' I enter '0'
    And for 'Duplicate Request' I enter '0'
    And for 'Other*' I enter '0'

# Section: 'V.B.(2). DISPOSITION OF FOIA REQUESTS -- "OTHER" REASONS FOR "FULL DENIALS BASED ON REASONS OTHER THAN EXEMPTIONS"'
    And I click 'V.B.(2). DISPOSITION OF FOIA REQUESTS -- "OTHER" REASONS FOR "FULL DENIALS BASED ON REASONS OTHER THAN EXEMPTIONS"'
    And I wait 3 seconds
    And I click 'Agency/Component'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And I click '"Other" Reasons for Denials'
    And I wait 3 seconds
    And for 'Description of "Other" Reasons for Denials from Chart B(1)' I enter 'N/A'
    And for 'Number of Times "Other" Reason Was Relied Upon' I enter '0'

# Section: 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED'
    And I click 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Ex. 1' I enter '0'
    And for 'Ex. 2' I enter '0'
    And for 'Ex. 3' I enter '0'
    And for 'Ex. 4' I enter '0'
    And for 'Ex. 5' I enter '2'
    And for 'Ex. 6' I enter '0'
    And for 'Ex. 7(A)' I enter '0'
    And for 'Ex. 7(B)' I enter '0'
    And for 'Ex. 7(C)' I enter '0'
    And for 'Ex. 7(D)' I enter '0'
    And for 'Ex. 7(E)' I enter '0'
    And for 'Ex. 7(F)' I enter '0'
    And for 'Ex. 8' I enter '0'
    And for 'Ex. 9' I enter '0'

# Section: 'VI.A. ADMINISTRATIVE APPEALS OF INITIAL DETERMINATIONS OF FOIA REQUESTS - RECEIVED, PROCESSED, AND PENDING ADMINISTRATIVE APPEAL'
    And I click 'VI.A. ADMINISTRATIVE APPEALS OF INITIAL DETERMINATIONS OF FOIA REQUESTS - RECEIVED, PROCESSED, AND PENDING ADMINISTRATIVE APPEAL'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Appeals Pending as of Start of Fiscal Year' I enter '0'
    And for 'Number of Appeals Received in Fiscal Year' I enter '0'
    And for 'Number of Appeals Processed in Fiscal Year' I enter '0'

# Section: 'VI.B. DISPOSITION OF ADMINISTRATIVE APPEALS -- ALL PROCESSED APPEALS'
    And I click 'VI.B. DISPOSITION OF ADMINISTRATIVE APPEALS -- ALL PROCESSED APPEALS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number Affirmed on Appeal' I enter '0'
    And for 'Number Partially Affirmed & Partially Reversed/Remanded on Appeal' I enter '0'
    And for 'Number Completely Reversed/Remanded on Appeal' I enter '0'
    And for 'Number of Appeals Closed for Other Reasons' I enter '0'

# Section: 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED'
    And I click 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Ex. 1' I enter '0'
    And for 'Ex. 2' I enter '0'
    And for 'Ex. 3' I enter '0'
    And for 'Ex. 4' I enter '0'
    And for 'Ex. 5' I enter '0'
    And for 'Ex. 6' I enter '0'
    And for 'Ex. 7(A)' I enter '0'
    And for 'Ex. 7(B)' I enter '0'
    And for 'Ex. 7(C)' I enter '0'
    And for 'Ex. 7(D)' I enter '0'
    And for 'Ex. 7(E)' I enter '0'
    And for 'Ex. 7(F)' I enter '0'
    And for 'Ex. 8' I enter '0'
    And for 'Ex. 9' I enter '0'

# Section: 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS'
    And I click 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'No Records' I enter '0'
    And for 'Records Referred at Initial Request Level' I enter '0'
    And for 'Request Withdrawn' I enter '0'
    And for 'Fee-Related Reason' I enter '0'
    And for 'Records Not Reasonably Described' I enter '0'
    And for 'Improper FOIA Request for Other Reason' I enter '0'
    And for 'Not Agency Record' I enter '0'
    And for 'Duplicate Request or Appeal' I enter '0'
    And for 'Request in Litigation' I enter '0'
    And for 'Appeal Based Solely on Denial of Request for Expedited Processing' I enter '0'
    And for 'Other*' I enter '0'

# Section: 'VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS'
    And I click 'VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS'
    And I wait 3 seconds
    And I click 'Agency/Component'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And I click '"Other" Reasons Information'
    And I wait 3 seconds
    And for 'Description of "Other" Reasons for Denials from Chart B(1)' I enter 'N/A'
    And for 'Number of Times "Other" Reason Was Relied Upon' I enter '0'

# Section: 'VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS'
    And I click 'VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Median Number of Days' I enter 'N/A'
    And for 'Average Number of Days' I enter 'N/A'
    And for 'Lowest Number of Days' I enter 'N/A'
    And for 'Highest Number of Days' I enter 'N/A'
    And for 'Agency Overall Median Number of Days' I enter 'N/A'
    And for 'Agency Overall Average Number of Days' I enter 'N/A'

# Section: 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS'
    And I click 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And I click 'Oldest'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '2nd'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '3rd'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '4th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '5th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '6th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '7th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '8th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '9th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '10th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click 'Agency Overall Oldest Appeal'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 2nd'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 3rd'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 4th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 5th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 6th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 7th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 8th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 9th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 10th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Appeal' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'

# Section: 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS'
    And I click 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS'
    And I wait 3 seconds
    And I click 'Simple'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
# By change the following field it will update
# the corresponding field in Agency component node.
    And for 'Median Number of Days' I enter '107'
    And for 'Average Number of Days' I enter '103.4'
    And for 'Lowest Number of Days' I enter '43'
    And for 'Highest Number of Days' I enter '200'
    And I click 'Complex'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Median Number of Days' I enter '99'
    And for 'Average Number of Days' I enter '122.2'
    And for 'Lowest Number of Days' I enter '47'
    And for 'Highest Number of Days' I enter '233'
    And I click 'Expedited Processing'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Median Number of Days' I enter 'N/A'
    And for 'Average Number of Days' I enter 'N/A'
    And for 'Lowest Number of Days' I enter 'N/A'
    And for 'Highest Number of Days' I enter 'N/A'
    And I click 'Agency Overall -- Simple'
    And I wait 3 seconds
    And for 'Agency Overall Median Number of Days' I enter 'N/A'
    And for 'Agency Overall Average Number of Days' I enter '22.58'
    And I click 'Agency Overall -- Complex'
    And I wait 3 seconds
    And for 'Agency Overall Median Number of Days' I enter '57'
    And for 'Agency Overall Average Number of Days' I enter '220.7'
    And I click 'Agency Overall -- Expedited Processing'
    And I wait 3 seconds
    And for 'Agency Overall Median Number of Days' I enter 'N/A'
    And for 'Agency Overall Average Number of Days' I enter 'N/A'

# Section: 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED'
    And I click 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED'
    And I wait 3 seconds
    And I click 'Simple'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Median Number of Days' I enter 'N/A'
    And for 'Average Number of Days' I enter 'N/A'
    And for 'Lowest Number of Days' I enter 'N/A'
    And for 'Highest Number of Days' I enter 'N/A'
    And I click 'Complex'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Median Number of Days' I enter '109'
    And for 'Average Number of Days' I enter '109'
    And for 'Lowest Number of Days' I enter '68'
    And for 'Highest Number of Days' I enter '149'
    And I click 'Expedited Processing'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Median Number of Days' I enter 'N/A'
    And for 'Average Number of Days' I enter 'N/A'
    And for 'Lowest Number of Days' I enter 'N/A'
    And for 'Highest Number of Days' I enter 'N/A'
    And I click 'Agency Overall Simple'
    And I wait 3 seconds
    And for 'Agency Overall Median Number of Days' I enter 'N/A'
    And for 'Agency Overall Average Number of Days' I enter 'N/A'
    And I click 'Agency Overall -- Complex'
    And I wait 3 seconds
    And for 'Agency Overall Median Number of Days' I enter '109'
    And for 'Agency Overall Average Number of Days' I enter '109'
    And I click 'Agency Overall -- Expedited Processing'
    And I wait 3 seconds
    And for 'Agency Overall Median Number of Days' I enter 'N/A'
    And for 'Agency Overall Average Number of Days' I enter 'N/A'

# Section: 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS'
    And I click 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for '<1-20 Days' I enter '0'
    And for '21-40 Days' I enter '0'
    And for '41-60 Days' I enter '2'
    And for '61-80 Days' I enter '0'
    And for '81-100 Days' I enter '0'
    And for '101-120 Days' I enter '2'
    And for '121-140 Days' I enter '0'
    And for '141-160 Days' I enter '0'
    And for '161-180 Days' I enter '0'
    And for '181-200 Days' I enter '1'
    And for '201-300 Days' I enter '0'
    And for '301-400 Days' I enter '0'
    And for '400+ Days' I enter '0'

# Section: 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS'
    And I click 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for '<1-20 Days' I enter '0'
    And for '21-40 Days' I enter '0'
    And for '41-60 Days' I enter '1'
    And for '61-80 Days' I enter '0'
    And for '81-100 Days' I enter '2'
    And for '101-120 Days' I enter '0'
    And for '121-140 Days' I enter '1'
    And for '141-160 Days' I enter '0'
    And for '161-180 Days' I enter '0'
    And for '181-200 Days' I enter '0'
    And for '201-300 Days' I enter '1'
    And for '301-400 Days' I enter '0'
    And for '400+ Days' I enter '0'

# Section: 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS'
    And I click 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for '<1-20 Days' I enter '0'
    And for '21-40 Days' I enter '0'
    And for '41-60 Days' I enter '0'
    And for '61-80 Days' I enter '0'
    And for '81-100 Days' I enter '0'
    And for '101-120 Days' I enter '0'
    And for '141-160 Days' I enter '0'
    And for '161-180 Days' I enter '0'
    And for '181-200 Days' I enter '0'
    And for '201-300 Days' I enter '0'
    And for '301-400 Days' I enter '0'
    And for '400+ Days' I enter '0'

# Section: 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS'
    And I click 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And I click 'Simple'
    And I wait 3 seconds
    And for 'Number Pending' I enter '1'
    And for 'Median Number of Days' I enter '58'
    And for 'Average Number of Days' I enter '58'
    And I click 'Complex'
    And I wait 3 seconds
    And for 'Number Pending' I enter '5'
    And for 'Median Number of Days' I enter '71'
    And for 'Average Number of Days' I enter '79.4'
    And I click 'Expedited Processing'
    And I wait 3 seconds
    And for 'Number Pending' I enter '0'
    And for 'Median Number of Days' I enter '0'
    And for 'Average Number of Days' I enter '0'
    And I click 'Agency Overall -- Simple'
    And I wait 3 seconds
    And for 'Agency Overall Number Pending' I enter '1'
    And for 'Agency Overall Median Number of Days' I enter '58'
    And for 'Agency Overall Average Number of Days' I enter '58'
    And I click 'Agency Overall -- Complex'
    And I wait 3 seconds
    And for 'Agency Overall Number Pending' I enter '5'
    And for 'Agency Overall Median Number of Days' I enter '71'
    And for 'Agency Overall Average Number of Days' I enter '79.4'
    And I click 'Agency Overall -- Expedited Processing'
    And I wait 3 seconds
    And for 'Agency Overall Number Pending' I enter '0'
    And for 'Agency Overall Median Number of Days' I enter '0'
    And for 'Agency Overall Average Number of Days' I enter '0'

# Section: 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS'
    And I click 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And I click 'Oldest'
    And I wait 3 seconds
    And for 'Date' I enter '2021-03-25'
    And for 'Number of Days Pending' I enter '132'
    And I click '2nd'
    And I wait 3 seconds
    And for 'Date' I enter '2021-06-11'
    And for 'Number of Days Pending' I enter '77'
    And I click '3rd'
    And I wait 3 seconds
    And for 'Date' I enter '2021-06-22'
    And for 'Number of Days Pending' I enter '71'
    And I click '4th'
    And I wait 3 seconds
    And for 'Date' I enter '2021-07-09'
    And for 'Number of Days Pending' I enter '59'
    And I click '5th'
    And I wait 3 seconds
    And for 'Date' I enter '2021-07-12'
    And for 'Number of Days Pending' I enter '58'
    And I click '6th'
    And I wait 3 seconds
    And for 'Date' I enter '2021-07-12'
    And for 'Number of Days Pending' I enter '58'
    And I click '7th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '8th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '9th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '10th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter 'N/A'
    And I click 'Agency Overall Oldest Request'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter '2021-03-25'
    And for 'Agency Overall Number of Days Pending' I enter '132'
    And I click 'Agency Overall 2nd'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter '2021-06-11'
    And for 'Agency Overall Number of Days Pending' I enter '77'
    And I click 'Agency Overall 3nd'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter '2021-06-22'
    And for 'Agency Overall Number of Days Pending' I enter '71'
    And I click 'Agency Overall 4th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter '2021-07-09'
    And for 'Agency Overall Number of Days Pending' I enter '59'
    And I click 'Agency Overall 5th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter '2021-07-12'
    And for 'Agency Overall Number of Days Pending' I enter '58'
    And I click 'Agency Overall 6th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter '2021-07-12'
    And for 'Agency Overall Number of Days Pending' I enter '58'
    And I click 'Agency Overall 7th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 8th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 9th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 10th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'

# Section: 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING'
    And I click 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number Granted' I enter '0'
    And for 'Number Denied' I enter '0'
    And for 'Median Number of Days to Adjudicate' I enter 'N/A'
    And for 'Average Number of Days to Adjudicate' I enter 'N/A'
    And for 'Number Adjudicated Within Ten Calendar Days' I enter '0'
    And for 'Agency Overall Median Number of Days to Adjudicate' I enter 'N/A'
    And for 'Agency Overall Average Number of Days to Adjudicate' I enter 'N/A'

# Section: 'VIII.B. Requests for Fee Waiver'
    And I click 'VIII.B. Requests for Fee Waiver'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number Granted' I enter '15'
    And for 'Number Denied' I enter '0'
    And for 'Median Number of Days to Adjudicate' I enter 'N/A'
    And for 'Average Number of Days to Adjudicate' I enter 'N/A'
    And for 'Agency Overall Median Number of Days to Adjudicate' I enter 'N/A'
    And for 'Agency Overall Average Number of Days to Adjudicate' I enter 'N/A'

# Section: 'IX. FOIA Personnel and Costs'
    And I click 'IX. FOIA Personnel and Costs'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And I click 'PERSONNEL'
    And I wait 3 seconds
    And for 'Number of "Full-Time FOIA Employees"' I enter '0'
    And for 'Number of "Equivalent Full-Time FOIA Employees"' I enter '0.10'
    And I click 'COSTS'
    And I wait 3 seconds
    And for 'Processing Costs' I enter '5000.00'
    And for 'Litigation-Related Costs' I enter '0.00'

# Section: 'X. Fees Collected for Processing Request'
    And I click 'X. Fees Collected for Processing Requests'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Total Amount of Fees Collected' I enter '0.00'

# Section: 'XI.A. Number of Times Subsection (C) Used'
    And I click 'XI.A. Number of Times Subsection (C) Used'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Times Subsection Used' I enter '0'

# Section: 'XI.B. Number of Subsection (A)(2) Postings'
    And I click 'XI.B. Number of Subsection (A)(2) Postings'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Records Posted by the FOIA Office' I enter '0'
    And for 'Number of Records Posted by Program Offices' I enter '50'

# Section: 'XII.A. Backlogs of FOIA Requests and Administrative Appeals'
    And I click 'XII.A. Backlogs of FOIA Requests and Administrative Appeals'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Backlogged Requests as of End of Fiscal Year' I enter '6'
    And for 'Number of Backlogged Appeals as of End of Fiscal Year' I enter '0'

# Section: 'XII.B. CONSULTATIONS ON FOIA REQUESTS -- RECEIVED, PROCESSED, AND PENDING CONSULTATIONS'
    And I click 'XII.B. CONSULTATIONS ON FOIA REQUESTS -- RECEIVED, PROCESSED, AND PENDING CONSULTATIONS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Consultations Received from Other Agencies that were Pending at the Agency as of Start of the Fiscal Year' I enter '0'
    And for 'Number of Consultations Received from Other Agencies During the Fiscal Year' I enter '3'
    And for 'Number of Consultations Received from Other Agencies that were Processed by the Agency During the Fiscal Year' I enter '3'

# Section: 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY'
    And I click 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And I click 'Oldest'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '2nd'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '3rd'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '4th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '5th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '6th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '7th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '8th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '9th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click '10th'
    And I wait 3 seconds
    And for 'Date' I enter 'N/A'
    And for 'Number of Days Pending' I enter '0'
    And I click 'Agency Overall Oldest Consultation'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 2nd'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 3nd'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 4th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 5th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 6th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 7th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 8th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 9th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'
    And I click 'Agency Overall 10th'
    And I wait 3 seconds
    And for 'Agency Overall Date of Receipt' I enter 'N/A'
    And for 'Agency Overall Number of Days Pending' I enter '0'

# Section: 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED'
    And I click 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And I click 'NUMBER RECEIVED'
    And I wait 3 seconds
    And for "Number Received During Fiscal Year from Last Year's Annual Report" I enter '12'
    And for 'Number Received During Fiscal Year from Current Annual Report' I enter '21'
    And I click 'NUMBER PROCESSED'
    And I wait 3 seconds
    And for "Number Processed During Fiscal Year from Last Year's Annual Report" I enter '19'
    And for 'Number Processed During Fiscal Year from Current Annual Report' I enter '16'

# Section: 'XII.D.(2). COMPARISON OF BACKLOGGED REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT'
    And I click 'XII.D.(2). COMPARISON OF BACKLOGGED REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Backlogged Requests as of End of the Fiscal Year from Previous Annual Report' I enter '1'
    And for 'Number of Backlogged Requests as of End of the Fiscal Year from Current Annual Report' I enter '6'

# Section: "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC"
    And I click "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC"
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And I click 'NUMBER RECEIVED'
    And I wait 3 seconds
    And for "Number Received During Fiscal Year from Last Year's Annual Report" I enter '0'
    And for 'Number Received During Fiscal Year from Current Annual Report' I enter '0'
    And I click 'NUMBER PROCESSED'
    And I wait 3 seconds
    And for "Number Processed During Fiscal Year from Last Year's Annual Report" I enter '0'
    And for 'Number Processed During Fiscal Year from Current Annual Report' I enter '0'

# Section: 'XII.E.(2). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- BACKLOGGED APPEALS'
    And I click 'XII.E.(2). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- BACKLOGGED APPEALS'
    And I wait 3 seconds
    And I select "ASC" from "Agency/Component"
    And for 'Number of Backlogged Appeals as of End of the Fiscal Year from Previous Annual Report' I enter '0'
    And for 'Number of Backlogged Appeals as of End of the Fiscal Year from Current Annual Report' I enter '0'

# Save Annual Report in "Draft" state.
    And I select "Draft" from "Change to"
    And I press the 'Save' button
    And I wait 5 seconds
    And I click 'Edit'
    And I wait 5 seconds
# Save Annual Report in "Submitted to OIP" state.
    And I select "Submitted to OIP" from "Change to"
    And I press the 'Save' button
    And I wait 5 seconds
    And I click 'Edit'
    And I wait 5 seconds
# Save Annual Report in "Cleared" state.
    And I select "Cleared" from "Change to"
    And I press the 'Save' button
    And I wait 5 seconds

