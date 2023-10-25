@annual_foia_report_data
Feature: Annual FOIA Report Data Feature
  In order to create Annual FOIA Reports
  As an Administrator
  I should be able to create and edit an Annual FOIA Report Data entity.

  Background:
    Given agency terms:
      | name                    | field_agency_abbreviation | description |format    | language |
      | Federal Testing Agency  | FTA                       | description |plain_text| en       |
    Given agency_component content:
      | title                   | field_agency              | field_rep_start | field_agency_comp_abbreviation |
      | est Agency Component 1T | Federal Testing Agency    | 2019-01-01      | ABCDEF                         |

  @api @javascript
  Scenario: Create an Annual FOIA Report Data node.
    Given I am logged in as a user with the 'Administrator' role
    And I am at 'node/add/annual_foia_report_data'
    And I select "Federal Testing Agency" from "Agency"
    And I wait 5 seconds
    And for 'FOIA Annual Report Year' I enter '2019'
    And for 'Date Prepared' I enter '08/22/2019'
    And I check the box "FTA"
    When I press the 'Save and continue' button
    Then I should see the following success messages:
      | Success messages                                  |
      | FTA - 2019 - Annual FOIA Report has been created. |

  @api
  Scenario: Agency Administrator can add Annual FOIA Reports
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add"
    Then I should see the link "Annual FOIA Report Data"

  @api @javascript
  Scenario: Agency Administrator can save Annual FOIA Reports in all workflow states
    When I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/annual_foia_report_data"
    And for 'FOIA Annual Report Year' I enter '2023'
    And I select "Federal Testing Agency" from "Agency"
    And I wait 5 seconds
    And I check the box "FTA"
    When I press the 'Save and continue' button
    Then I should see the following success messages:
      | Success messages                                        |
      | FTA - 2023 - Annual FOIA Report has been created. |
    And I select "Submitted to OIP" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Success messages                                            |
      | FTA - 2023 - Annual FOIA Report has been updated. |
    And save the current URL
    And I click 'Edit'
    And I select "Cleared" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Success messages                                            |
      | FTA - 2023 - Annual FOIA Report has been updated. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Published" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Success messages                                            |
      | FTA - 2023 - Annual FOIA Report has been updated. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Back with Agency" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Success messages                                            |
      | FTA - 2023 - Annual FOIA Report has been updated. |

  @api @javascript
  Scenario: There is a button for adding placeholders for component data
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/annual_foia_report_data"
    And I select "Federal Testing Agency" from "Agency"
    And I wait 5 seconds
    And I check the box "FTA"
    And for 'FOIA Annual Report Year' I enter '2019'
    And I press the 'Save and continue' button
    And I click 'IV. Exemption 3 Statutes'
    Then I should see "Add placeholders for component data below"

  @api @javascript
  Scenario: The validate button can be used to validate the report
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/annual_foia_report_data"
    And I press "Validate"
    And I wait 3 seconds
    Then I should see "This field is required."

  @api @javascript
  Scenario: Agency Administrator see the option to bulk-publish annual reports
    Given annual_foia_report_data content:
      | field_agency | field_foia_annual_report_yr | moderation_state |
      | Federal Testing Agency | 2023 | cleared |
    And I am logged in as a user with the 'Agency Administrator' role
    And I am on "/admin/content/reports"
    And I select "Publish foia annual reports" from "Action"

  @api
  Scenario: The Components should be required for annual reports
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/annual_foia_report_data"
    When I press the 'Save and continue' button
    Then I should see "Components field is required"

  @api @javascript
  Scenario: All of the necessary annual report fields are available.
    Given I am logged in as a user with the "Administrator" role
    And I am at "node/add/annual_foia_report_data"
    And I select "Federal Testing Agency" from "Agency"
    And I wait 5 seconds
    And I check the box "FTA"
    And for 'FOIA Annual Report Year' I enter '2019'
    And I press the 'Save and continue' button
    And I wait 5 seconds
    And I ignore the admin menu
    # Section:'IV. Exemption 3 Statutes'.
    And I click the section 'IV. Exemption 3 Statutes'
    And I click 'Statute' in the 'IV. Exemption 3 Statutes' section
    And for 'Statute' in the 'IV. Exemption 3 Statutes' section I enter 'N/A'
    And for 'Type of Information Withheld' in the 'IV. Exemption 3 Statutes' section I enter 'N/A'
    And for 'Case Citation' in the 'IV. Exemption 3 Statutes' section I enter 'N/A'
    And I click 'Agency/Component' in the 'IV. Exemption 3 Statutes' section
    And I select "ABCDEF" from "Agency/Component" in the 'IV. Exemption 3 Statutes' section
    And for 'Number of Times Relied Upon by Agency/Component' in the 'IV. Exemption 3 Statutes' section I enter '0'
    # Section:'V.A. FOIA REQUESTS -- RECEIVED, PROCESSED AND PENDING FOIA REQUESTS'.
    And I click the section 'V.A. FOIA REQUESTS -- RECEIVED, PROCESSED AND PENDING FOIA REQUESTS'
    And I select "ABCDEF" from "Agency/Component" in the 'V.A. FOIA REQUESTS -- RECEIVED, PROCESSED AND PENDING FOIA REQUESTS' section
    And for 'Number of Requests Pending as of Start of Fiscal Year' I enter '1'
    And for 'Number of Requests Received in Fiscal Year' I enter '21'
    And for 'Number of Requests Processed in Fiscal Year' I enter '16'
    # Section:'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS'.
    And I click the section 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS'
    And I click 'Agency/Component' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section
    And I select "ABCDEF" from "Agency/Component" in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section
    And for 'Number of Full Grants' I enter '3'
    And for 'Number of Partial Grants/Partial Denials' I enter '1'
    And for 'Number of Full Denials Based on Exemptions' I enter '0'
    And I click 'Number of Full Denials Based on Reasons Other than Exemptions'
    And for 'No Records' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section I enter '4'
    And for 'All Records Referred to Another Component or Agency' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section I enter '0'
    And for 'Request Withdrawn' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section I enter '2'
    And for 'Fee-Related Reason' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section I enter '0'
    And for 'Records Not Reasonably Described' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section I enter '0'
    And for 'Improper FOIA Request for Other Reason' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section I enter '6'
    And for 'Not Agency Record' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section I enter '0'
    And for 'Duplicate Request' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section I enter '0'
    And for 'Other*' in the 'V.B.(1). DISPOSITION OF FOIA REQUESTS -- ALL PROCESSED REQUESTS' section I enter '0'
    # Section: 'V.B.(2). DISPOSITION OF FOIA REQUESTS -- "OTHER" REASONS FOR "FULL DENIALS BASED ON REASONS OTHER THAN EXEMPTIONS"'
    And I click the section 'V.B.(2). DISPOSITION OF FOIA REQUESTS -- "OTHER" REASONS FOR "FULL DENIALS BASED ON REASONS OTHER THAN EXEMPTIONS"'
    And I click 'Agency/Component' in the 'V.B.(2). DISPOSITION OF FOIA REQUESTS -- "OTHER" REASONS FOR "FULL DENIALS BASED ON REASONS OTHER THAN EXEMPTIONS"' section
    And I select "ABCDEF" from "Agency/Component" in the 'V.B.(2). DISPOSITION OF FOIA REQUESTS -- "OTHER" REASONS FOR "FULL DENIALS BASED ON REASONS OTHER THAN EXEMPTIONS"' section
    And I click '"Other" Reasons for Denials'
    And for 'Description of "Other" Reasons for Denials from Chart B(1)' in the 'V.B.(2). DISPOSITION OF FOIA REQUESTS -- "OTHER" REASONS FOR "FULL DENIALS BASED ON REASONS OTHER THAN EXEMPTIONS"' section and the '"Other" Reasons for Denials' sub-section I enter 'N/A'
    And for 'Number of Times "Other" Reason Was Relied Upon' in the 'V.B.(2). DISPOSITION OF FOIA REQUESTS -- "OTHER" REASONS FOR "FULL DENIALS BASED ON REASONS OTHER THAN EXEMPTIONS"' section and the '"Other" Reasons for Denials' sub-section I enter '0'
    # Section: 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED'
    And I click the section 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED'
    And I select "ABCDEF" from "Agency/Component" in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section
    And for 'Ex. 1' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 2' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 3' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 4' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 5' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '2'
    And for 'Ex. 6' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(A)' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(B)' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(C)' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(D)' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(E)' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(F)' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 8' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 9' in the 'V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    # Section: 'VI.A. ADMINISTRATIVE APPEALS OF INITIAL DETERMINATIONS OF FOIA REQUESTS - RECEIVED, PROCESSED, AND PENDING ADMINISTRATIVE APPEAL'
    And I click the section 'VI.A. ADMINISTRATIVE APPEALS OF INITIAL DETERMINATIONS OF FOIA REQUESTS - RECEIVED, PROCESSED, AND PENDING ADMINISTRATIVE APPEAL'
    And I select "ABCDEF" from "Agency/Component" in the 'VI.A. ADMINISTRATIVE APPEALS OF INITIAL DETERMINATIONS OF FOIA REQUESTS - RECEIVED, PROCESSED, AND PENDING ADMINISTRATIVE APPEAL' section
    And for 'Number of Appeals Pending as of Start of Fiscal Year' I enter '0'
    And for 'Number of Appeals Received in Fiscal Year' I enter '0'
    And for 'Number of Appeals Processed in Fiscal Year' I enter '0'
    # Section: 'VI.B. DISPOSITION OF ADMINISTRATIVE APPEALS -- ALL PROCESSED APPEALS'
    And I click the section 'VI.B. DISPOSITION OF ADMINISTRATIVE APPEALS -- ALL PROCESSED APPEALS'
    And I select "ABCDEF" from "Agency/Component" in the 'VI.B. DISPOSITION OF ADMINISTRATIVE APPEALS -- ALL PROCESSED APPEALS' section
    And for 'Number Affirmed on Appeal' I enter '0'
    And for 'Number Partially Affirmed & Partially Reversed/Remanded on Appeal' I enter '0'
    And for 'Number Completely Reversed/Remanded on Appeal' I enter '0'
    And for 'Number of Appeals Closed for Other Reasons' I enter '0'
    # Section: 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED'
    And I click the section 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED'
    And I select "ABCDEF" from "Agency/Component" in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section
    And for 'Ex. 1' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 2' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 3' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 4' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 5' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 6' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(A)' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(B)' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(C)' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(D)' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(E)' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 7(F)' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 8' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    And for 'Ex. 9' in the 'VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED' section I enter '0'
    # Section: 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS'
    And I click the section 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS'
    And I select "ABCDEF" from "Agency/Component" in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section
    And for 'No Records' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Records Referred at Initial Request Level' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Request Withdrawn' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Fee-Related Reason' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Records Not Reasonably Described' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Improper FOIA Request for Other Reason' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Not Agency Record' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Duplicate Request or Appeal' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Request in Litigation' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Appeal Based Solely on Denial of Request for Expedited Processing' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    And for 'Other*' in the 'VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS' section I enter '0'
    # Section: 'VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS'
    And I click the section 'VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS'
    And I click 'Agency/Component' in the 'VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS' section
    And I select "ABCDEF" from "Agency/Component" in the 'VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS' section
    And I click '"Other" Reasons Information'
    And for 'Description of "Other" Reasons for Denials from Chart B(1)' in the 'VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS' section and the '"Other" Reasons Information' sub-section I enter 'N/A'
    And for 'Number of Times "Other" Reason Was Relied Upon' in the 'VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS' section and the '"Other" Reasons Information' sub-section I enter '0'
    # Section: 'VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS'
    And I click the section 'VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS'
    And I select "ABCDEF" from "Agency/Component" in the 'VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS' section
    And for 'Median Number of Days' in the 'VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS' section I enter 'N/A'
    And for 'Average Number of Days' in the 'VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS' section I enter 'N/A'
    And for 'Lowest Number of Days' in the 'VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS' section I enter 'N/A'
    And for 'Highest Number of Days' in the 'VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS' section I enter 'N/A'
    # Section: 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS'
    And I click the section 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS'
    And I select "ABCDEF" from "Agency/Component" in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And I click 'Oldest' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the 'Oldest' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the 'Oldest' sub-section I enter '0'
    And I click '2nd' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '2nd' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '2nd' sub-section I enter '0'
    And I click '3rd' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '3rd' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '3rd' sub-section I enter '0'
    And I click '4th' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '4th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '4th' sub-section I enter '0'
    And I click '5th' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '5th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '5th' sub-section I enter '0'
    And I click '6th' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '6th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '6th' sub-section I enter '0'
    And I click '7th' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '7th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '7th' sub-section I enter '0'
    And I click '8th' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '8th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '8th' sub-section I enter '0'
    And I click '9th' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '9th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '9th' sub-section I enter '0'
    And I click '10th' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section
    And for 'Date' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '10th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VI.C.(5). TEN OLDEST PENDING ADMINISTRATIVE APPEALS' section and the '10th' sub-section I enter '0'
    # Section: 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS'
    And I click the section 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS'
    And I click 'Simple' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And I select "ABCDEF" from "Agency/Component" in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And for 'Median Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '106'
    And for 'Average Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '103.4'
    And for 'Lowest Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '43'
    And for 'Highest Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '200'
    And I click 'Complex' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And for 'Median Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Complex' sub-section I enter '99'
    And for 'Average Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Complex' sub-section I enter '122.2'
    And for 'Lowest Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Complex' sub-section I enter '47'
    And for 'Highest Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Complex' sub-section I enter '233'
    And I click 'Expedited Processing' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section
    And for 'Median Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Expedited Processing' sub-section I enter 'N/A'
    And for 'Average Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Expedited Processing' sub-section I enter 'N/A'
    And for 'Lowest Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Expedited Processing' sub-section I enter 'N/A'
    And for 'Highest Number of Days' in the 'VII.A. FOIA REQUESTS -- RESPONSE TIME FOR ALL PROCESSED PERFECTED REQUESTS' section and the 'Expedited Processing' sub-section I enter 'N/A'
    # Section: 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED'
    And I click the section 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED'
    And I click 'Simple' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section
    And I select "ABCDEF" from "Agency/Component" in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section
    And for 'Median Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Simple' sub-section I enter 'N/A'
    And for 'Average Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Simple' sub-section I enter 'N/A'
    And for 'Lowest Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Simple' sub-section I enter 'N/A'
    And for 'Highest Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Simple' sub-section I enter 'N/A'
    And I click 'Complex' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section
    And for 'Median Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Complex' sub-section I enter '109'
    And for 'Average Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Complex' sub-section I enter '109'
    And for 'Lowest Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Complex' sub-section I enter '68'
    And for 'Highest Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Complex' sub-section I enter '149'
    And I click 'Expedited Processing' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section
    And for 'Median Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Expedited Processing' sub-section I enter 'N/A'
    And for 'Average Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Expedited Processing' sub-section I enter 'N/A'
    And for 'Lowest Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Expedited Processing' sub-section I enter 'N/A'
    And for 'Highest Number of Days' in the 'VII.B. PROCESSED REQUESTS -- RESPONSE TIME FOR PERFECTED REQUESTS IN WHICH INFORMATION WAS GRANTED' section and the 'Expedited Processing' sub-section I enter 'N/A'
    # Section: 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS'
    And I click the section 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS'
    And I select "ABCDEF" from "Agency/Component" in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section
    And for '<1-20 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '21-40 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '41-60 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '2'
    And for '61-80 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '81-100 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '101-120 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '2'
    And for '121-140 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '141-160 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '161-180 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '181-200 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '1'
    And for '201-300 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '301-400 Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '400+ Days' in the 'VII.C.(1). PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    # Section: 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS'
    And I click the section 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS'
    And I select "ABCDEF" from "Agency/Component" in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section
    And for '<1-20 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '21-40 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '41-60 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '1'
    And for '61-80 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '81-100 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '2'
    And for '101-120 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '121-140 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '1'
    And for '141-160 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '161-180 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '181-200 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '201-300 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '1'
    And for '301-400 Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '400+ Days' in the 'VII.C.(2). PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    # Section: 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS'
    And I click the section 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS'
    And I select "ABCDEF" from "Agency/Component" in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section
    And for '<1-20 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '21-40 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '41-60 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '61-80 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '81-100 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '101-120 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '141-160 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '161-180 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '181-200 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '201-300 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '301-400 Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    And for '400+ Days' in the 'VII.C.(3). PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS' section I enter '0'
    # Section: 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS'
    And I click the section 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS'
    And I select "ABCDEF" from "Agency/Component" in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section
    And I click 'Simple' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section
    And for 'Number Pending' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '1'
    And for 'Median Number of Days' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '58'
    And for 'Average Number of Days' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section and the 'Simple' sub-section I enter '58'
    And I click 'Complex' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section
    And for 'Number Pending' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section and the 'Complex' sub-section I enter '5'
    And for 'Median Number of Days' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section and the 'Complex' sub-section I enter '71'
    And for 'Average Number of Days' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section and the 'Complex' sub-section I enter '79.4'
    And I click 'Expedited Processing' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section
    And for 'Number Pending' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section and the 'Expedited Processing' sub-section I enter '0'
    And for 'Median Number of Days' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section and the 'Expedited Processing' sub-section I enter '0'
    And for 'Average Number of Days' in the 'VII.D. PENDING REQUESTS -- ALL PENDING PERFECTED REQUESTS' section and the 'Expedited Processing' sub-section I enter '0'
    # Section: 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS'
    And I click the section 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS'
    And I select "ABCDEF" from "Agency/Component" in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And I click 'Oldest' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the 'Oldest' sub-section I enter '2021-03-25'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the 'Oldest' sub-section I enter '132'
    And I click '2nd' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '2nd' sub-section I enter '2021-06-11'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '2nd' sub-section I enter '77'
    And I click '3rd' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '3rd' sub-section I enter '2021-06-22'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '3rd' sub-section I enter '71'
    And I click '4th' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '4th' sub-section I enter '2021-07-09'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '4th' sub-section I enter '59'
    And I click '5th' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '5th' sub-section I enter '2021-07-12'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '5th' sub-section I enter '58'
    And I click '6th' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '6th' sub-section I enter '2021-07-12'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '6th' sub-section I enter '58'
    And I click '7th' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '7th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '7th' sub-section I enter '0'
    And I click '8th' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '8th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '8th' sub-section I enter '0'
    And I click '9th' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '9th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '9th' sub-section I enter '0'
    And I click '10th' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section
    And for 'Date' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '10th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'VII.E. PENDING REQUESTS -- TEN OLDEST PENDING PERFECTED REQUESTS' section and the '10th' sub-section I enter 'N/A'
    # Section: 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING'
    And I click the section 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING'
    And I select "ABCDEF" from "Agency/Component" in the 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING' section
    And for 'Number Granted' in the 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING' section I enter '0'
    And for 'Number Denied' in the 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING' section I enter '0'
    And for 'Median Number of Days to Adjudicate' in the 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING' section I enter 'N/A'
    And for 'Average Number of Days to Adjudicate' in the 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING' section I enter 'N/A'
    And for 'Number Adjudicated Within Ten Calendar Days' in the 'VIII.A. REQUESTS FOR EXPEDITED PROCESSING' section I enter '0'
    # Section: 'VIII.B. Requests for Fee Waiver'
    And I click the section 'VIII.B. Requests for Fee Waiver'
    And I select "ABCDEF" from "Agency/Component" in the 'VIII.B. Requests for Fee Waiver' section
    And for 'Number Granted' in the 'VIII.B. Requests for Fee Waiver' section I enter '15'
    And for 'Number Denied' in the 'VIII.B. Requests for Fee Waiver' section I enter '0'
    And for 'Median Number of Days to Adjudicate' in the 'VIII.B. Requests for Fee Waiver' section I enter 'N/A'
    And for 'Average Number of Days to Adjudicate' in the 'VIII.B. Requests for Fee Waiver' section I enter 'N/A'
    # Section: 'IX. FOIA Personnel and Costs'
    And I click the section 'IX. FOIA Personnel and Costs'
    And I select "ABCDEF" from "Agency/Component" in the 'IX. FOIA Personnel and Costs' section
    And I click 'PERSONNEL'
    And for 'Number of "Full-Time FOIA Employees"' I enter '0'
    And for 'Number of "Equivalent Full-Time FOIA Employees"' I enter '0.10'
    And I click 'COSTS'
    And for 'Processing Costs' I enter '5000.00'
    And for 'Litigation-Related Costs' I enter '0.00'
    # Section: 'X. Fees Collected for Processing Request'
    And I click the section 'X. Fees Collected for Processing Requests'
    And I select "ABCDEF" from "Agency/Component" in the 'X. Fees Collected for Processing Requests' section
    And for 'Total Amount of Fees Collected' I enter '0.00'
    # Section: 'XI.A. Number of Times Subsection (C) Used'
    And I click the section 'XI.A. Number of Times Subsection (C) Used'
    And I select "ABCDEF" from "Agency/Component" in the 'XI.A. Number of Times Subsection (C) Used' section
    And for 'Number of Times Subsection Used' I enter '0'
    # Section: 'XI.B. Number of Subsection (A)(2) Postings'
    And I click the section 'XI.B. Number of Subsection (A)(2) Postings'
    And I select "ABCDEF" from "Agency/Component" in the 'XI.B. Number of Subsection (A)(2) Postings' section
    And for 'Number of Records Posted by the FOIA Office' I enter '0'
    And for 'Number of Records Posted by Program Offices' I enter '50'
    # Section: 'XII.A. Backlogs of FOIA Requests and Administrative Appeals'
    And I click the section 'XII.A. Backlogs of FOIA Requests and Administrative Appeals'
    And I select "ABCDEF" from "Agency/Component" in the 'XII.A. Backlogs of FOIA Requests and Administrative Appeals' section
    And for 'Number of Backlogged Requests as of End of Fiscal Year' I enter '6'
    And for 'Number of Backlogged Appeals as of End of Fiscal Year' I enter '0'
    # Section: 'XII.B. CONSULTATIONS ON FOIA REQUESTS -- RECEIVED, PROCESSED, AND PENDING CONSULTATIONS'
    And I click the section 'XII.B. CONSULTATIONS ON FOIA REQUESTS -- RECEIVED, PROCESSED, AND PENDING CONSULTATIONS'
    And I select "ABCDEF" from "Agency/Component" in the 'XII.B. CONSULTATIONS ON FOIA REQUESTS -- RECEIVED, PROCESSED, AND PENDING CONSULTATIONS' section
    And for 'Number of Consultations Received from Other Agencies that were Pending at the Agency as of Start of the Fiscal Year' I enter '0'
    And for 'Number of Consultations Received from Other Agencies During the Fiscal Year' I enter '3'
    And for 'Number of Consultations Received from Other Agencies that were Processed by the Agency During the Fiscal Year' I enter '3'
    # Section: 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY'
    And I click the section 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY'
    And I select "ABCDEF" from "Agency/Component" in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And I click 'Oldest' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the 'Oldest' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the 'Oldest' sub-section I enter '0'
    And I click '2nd' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '2nd' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '2nd' sub-section I enter '0'
    And I click '3rd' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '3rd' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '3rd' sub-section I enter '0'
    And I click '4th' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '4th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '4th' sub-section I enter '0'
    And I click '5th' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '5th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '5th' sub-section I enter '0'
    And I click '6th' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '6th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '6th' sub-section I enter '0'
    And I click '7th' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '7th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '7th' sub-section I enter '0'
    And I click '8th' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '8th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '8th' sub-section I enter '0'
    And I click '9th' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '9th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '9th' sub-section I enter '0'
    And I click '10th' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section
    And for 'Date' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '10th' sub-section I enter 'N/A'
    And for 'Number of Days Pending' in the 'XII.C. CONSULTATIONS ON FOIA REQUESTS -- TEN OLDEST CONSULTATIONS RECEIVED FROM OTHER AGENCIES AND PENDING AT THE AGENCY' section and the '10th' sub-section I enter '0'
    # Section: 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED'
    And I click the section 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED'
    And I select "ABCDEF" from "Agency/Component" in the 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED' section
    And I click 'NUMBER RECEIVED' in the 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED' section
    And for "Number Received During Fiscal Year from Last Year's Annual Report" in the 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED' section and the 'NUMBER RECEIVED' sub-section I enter '12'
    And for 'Number Received During Fiscal Year from Current Annual Report' in the 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED' section and the 'NUMBER RECEIVED' sub-section I enter '21'
    And I click 'NUMBER PROCESSED' in the 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED' section
    And for "Number Processed During Fiscal Year from Last Year's Annual Report" in the 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED' section and the 'NUMBER PROCESSED' sub-section I enter '19'
    And for 'Number Processed During Fiscal Year from Current Annual Report' in the 'XII.D.(1). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- REQUESTS RECEIVED AND PROCESSED' section and the 'NUMBER PROCESSED' sub-section I enter '16'
    # Section: 'XII.D.(2). COMPARISON OF BACKLOGGED REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT'
    And I click the section 'XII.D.(2). COMPARISON OF BACKLOGGED REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT'
    And I select "ABCDEF" from "Agency/Component" in the 'XII.D.(2). COMPARISON OF BACKLOGGED REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT' section
    And for 'Number of Backlogged Requests as of End of the Fiscal Year from Previous Annual Report' I enter '1'
    And for 'Number of Backlogged Requests as of End of the Fiscal Year from Current Annual Report' I enter '6'
    # Section: "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC"
    And I click the section "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC"
    And I select "ABCDEF" from "Agency/Component" in the "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC" section
    And I click 'NUMBER RECEIVED' in the "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC" section
    And for "Number Received During Fiscal Year from Last Year's Annual Report" in the "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC" section and the 'NUMBER RECEIVED' sub-section I enter '0'
    And for 'Number Received During Fiscal Year from Current Annual Report' in the "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC" section and the 'NUMBER RECEIVED' sub-section I enter '0'
    And I click 'NUMBER PROCESSED' in the "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC" section
    And for "Number Processed During Fiscal Year from Last Year's Annual Report" in the "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC" section and the 'NUMBER PROCESSED' sub-section I enter '0'
    And for 'Number Processed During Fiscal Year from Current Annual Report' in the "XII.E.(1). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- APPEALS REC'D AND PROC" section and the 'NUMBER PROCESSED' sub-section I enter '0'
    # Section: 'XII.E.(2). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- BACKLOGGED APPEALS'
    And I click the section 'XII.E.(2). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- BACKLOGGED APPEALS'
    And I select "ABCDEF" from "Agency/Component" in the 'XII.E.(2). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- BACKLOGGED APPEALS' section
    And for 'Number of Backlogged Appeals as of End of the Fiscal Year from Previous Annual Report' I enter '0'
    And for 'Number of Backlogged Appeals as of End of the Fiscal Year from Current Annual Report' I enter '0'
