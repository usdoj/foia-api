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
      | title                   | field_agency              |
      | Test Agency Component 1 | Federal Testing Agency    |

  @api
  Scenario: Create an Annual FOIA Report Data node.
    Given I am logged in as a user with the 'Administrator' role
    And I am at 'node/add/annual_foia_report_data'
    And for 'Title' I enter '2019 Test Agency 1 Annual FOIA Report'
    And for 'Agency' I enter 'Federal Testing Agency'
    And for 'Components' I enter 'Test Agency Component 1'
    And for 'FOIA Annual Report Year' I enter '2019'
    And for 'field_date_prepared[0][value][date]' I enter '08/22/2019'
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been created. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section IV Exemption 3 Statutes.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    And I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |
    

  @api
  Scenario: Edit an Annual FOIA Report Data node Section V.A. FOIA REQUESTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section V.B.(1). --- Number of Full Denials Based on Reasons Other than Exemptions.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section V.B.(2). DISPOSITION OF FOIA REQUESTS -- "OTHER" REASONS FOR "FULL DENIALS BASED ON REASONS OTHER THAN EXEMPTIONS".
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section V.B.(3). DISPOSITION OF FOIA REQUESTS -- NUMBER OF TIMES EXEMPTIONS APPLIED.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VI.A. ADMINISTRATIVE APPEALS OF INITIAL DETERMINATIONS OF FOIA REQUESTS -- RECEIVED, PROCESSED, AND PENDING ADMINISTRATIVE APPEA.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VI.B. DISPOSITION OF ADMINISTRATIVE APPEALS -- ALL PROCESSED APPEALS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VI.C.(1). REASONS FOR DENIAL ON APPEAL -- NUMBER OF TIMES EXEMPTIONS APPLIED.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VI.C.(2). REASONS FOR DENIAL ON APPEAL -- REASONS OTHER THAN EXEMPTIONS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VI.C.(3). REASONS FOR DENIAL ON APPEAL -- "OTHER" REASONS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VI.C.(4). RESPONSE TIME FOR ADMINISTRATIVE APPEALS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VI. C. (5) ADMINISTRATIVE APPEALS - OLDEST DAYS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VII.A. FOIA REQUESTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VII. B. PROCESSED REQUESTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VII.C.1 PROCESSED SIMPLE REQUESTS -- RESPONSE TIME IN DAY INCREMENTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VII.C.2 PROCESSED COMPLEX REQUESTS -- RESPONSE TIME IN DAY INCREMENTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VII.C.3 PROCESSED REQUESTS GRANTED EXPEDITED PROCESSING -- RESPONSE TIME IN DAY INCREMENTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VII. D. PENDING REQUESTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VII. E. PENDING REQUESTS - OLDEST DAYS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VIII.A. REQUESTS FOR EXPEDITED PROCESSING.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section VIII.B. REQUESTS FOR FEE WAIVER.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section IX. FOIA PERSONNEL AND COSTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section X. FEES COLLECTED FOR PROCESSING REQUESTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section XI.A. NUMBER OF TIMES SUBSECTION (C) USED.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section XI.B. NUMBER OF SUBSECTION (A)(2) POSTINGS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section XII.A. BACKLOGS OF FOIA REQUESTS AND ADMINISTRATIVE APPEALS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section XII.B. CONSULTATIONS ON FOIA REQUESTS -- RECEIVED, PROCESSED, AND PENDING CONSULTATIONS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section XII. C. FOIA REQUESTS AND ADMINISTRATIVE APPEALS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section XII. D. (1). FOIA REQUESTS AND ADMINISTRATIVE APPEALS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section XII.D.(2). COMPARISON OF NUMBERS OF REQUESTS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- BACKLOGGED REQUESTS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section XII. E. (1). FOIA REQUESTS AND ADMINISTRATIVE APPEALS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |

  @api
  Scenario: Edit an Annual FOIA Report Data node Section XII.E.(2). COMPARISON OF NUMBERS OF ADMINISTRATIVE APPEALS FROM PREVIOUS AND CURRENT ANNUAL REPORT -- BACKLOGGED APPEALS.
    Given I am logged in as a user with the 'Administrator' role
    And I go to the 'node' type entity with the '2019 Test Agency 1 Annual FOIA Report' label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data 2019 Test Agency 1 Annual FOIA Report has been updated. |
