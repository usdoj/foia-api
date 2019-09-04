@administrator
Feature: Agency Administrator role
  In order to keep Agencies, Components, Reports, and Managers up to date
  As an Agency Administrator
  I should be able to administer Agency Manager user accounts, agencies,
  agency components, and Annual FOIA Reports

  @api @agency @experimental
  Scenario: Agency Administrator can administer user accounts with the Agency Manager role
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am at 'admin/structure/taxonomy/manage/agency/add'
    And for 'Name' I enter 'A Test Agency'
    When I press the 'Save' button
    Then I should see the following success messages:
      | Created new term A Test Agency. |
    When I am at 'admin/people/create'
    And for 'Email address' I enter 'alex@alex.com'
    And for 'Username' I enter 'Alex'
    And for 'Password' I enter 'abc123!@#'
    And for 'Confirm password' I enter 'abc123!@#'
    And I check the box 'Agency Manager'
    And for 'Agency' I enter 'A Test Agency'
    And I press the 'Create new account' button
    Then I should see the following success messages:
      | Created a new user account for Alex. No email has been sent. |
    When I am at 'admin/people'
    Then I should see 'Agency Manager' in the 'Alex' row
    When I click 'Edit' in the 'Alex' row
    And I press the 'Save' button
    Then I should see the following success messages:
      | The changes have been saved. |
    When I click 'Edit' in the 'Alex' row
    And I press the 'Cancel account' button
    And I press the 'Cancel account' button
    Then I should see the following success messages:
      | Alex has been disabled. |
    And the user 'Alex' is deleted

  @api
  Scenario: Agency Administrator can not administer user accounts with the (Agency) Administrator or Authenticated roles
    Given users:
      | name   | mail              | roles                |
      | Mini   | mini@example.com  | Administrator        |
      | Angus  | angus@example.com | Agency Administrator |
    When I am logged in as a user with the 'Agency Administrator' role
    And I am at 'admin/people/create'
    And for 'Email address' I enter 'arthur@arthur.com'
    And for 'Username' I enter 'Arthur'
    And for 'Password' I enter 'abc123!@#'
    And for 'Confirm password' I enter 'abc123!@#'
    And I uncheck the box 'Agency Manager'
    And I press the 'Create new account' button
    Then I should see the following success messages:
      | Created a new user account for Arthur. No email has been sent. |
    When I am at 'admin/people'
    Then I should see 'Administrator' in the 'Mini' row
    And I should not see 'Edit' in the 'Mini' row
    And I should see 'Agency Administrator' in the 'Angus' row
    And I should not see 'Edit' in the 'Angus' row
    And I should not see 'Edit' in the 'Arthur' row
    And I view the user 'Mini'
    And I attempt to delete the current entity
    Then the response status code should be 404
    When I am at 'admin/people'
    And I view the user 'Angus'
    And I attempt to delete the current entity
    Then the response status code should be 404
    When I am at 'admin/people'
    And I view the user 'Arthur'
    And I attempt to delete the current entity
    Then the response status code should be 404
    And the user 'Arthur' is deleted

  @api @agency
  Scenario: Agency Administrator can administer Agencies
    Given I am logged in as a user with the 'Agency Administrator' role
    When I am at 'admin/structure/taxonomy/manage/agency/add'
    And for 'Name' I enter 'A Test Agency'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Created new term A Test Agency. |
    And I am at 'admin/structure/taxonomy/manage/agency/overview'
    When I click 'Edit' in the 'A Test Agency' row
    Then I should see the link 'Delete'
    When I press the 'Save' button
    Then I should see the following success messages:
      | Updated term A Test Agency. |

  @api @experimental
  Scenario: Agency Administrator can administer Agency Components
    Given "agency" terms:
      | name  |field_agency_abbreviation| description |format    | language |
      | test  |DOJ                      | description |plain_text| en       |
    When I am logged in as a user with the 'Agency Administrator' role
    And I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'A Test Agency Component'
    And for 'Agency' I enter 'test'
    And for Abbreviation I enter 'TAC'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component A Test Agency Component has been created. |
    And I click 'Edit'
    When I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component A Test Agency Component has been updated. |
    When I click 'Delete'
    And I press the 'Delete' button
    Then I should see the following success messages:
      | The Agency Component A Test Agency Component has been deleted. |

  @api
  Scenario: Agency Administrator can view admin theme
    Given I am logged in as a user with the 'Administrator' role
    When I am at 'admin/people/permissions/agency_administrator'
    Then the "View the administration theme" checkbox should be checked

  @api
  Scenario: Agency Administrator can view admin toolbar
    Given I am logged in as a user with the 'Agency Administrator' role
    When I am on the homepage
    Then I should see the link 'Manage'

  @api
  Scenario: Agency Administrator can view unpublished content
    Given I am logged in as a user with the 'Agency Administrator' role
    When I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'A Test Agency Component'
    And I uncheck the box "Published"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component A Test Agency Component has been created. |

  @api @experimental
  Scenario: Administer webforms and submissions thereof
    Given I am logged in as a user with the 'Agency Administrator' role
    When I am at 'admin/structure/webform/add'
    And for 'Machine-readable name' I enter 'a_test_webform'
    And for 'Title' I enter 'A Test Webform'
    When I press the 'Save' button
    Then I should see the following success messages:
      | Webform A Test Webform created. |
    When I press the 'Save elements' button
    Then I should see the following success messages:
      | Webform A Test Webform elements saved. |
    When I click 'View'
    And for 'First name' I enter 'A Test First name'
    And for 'Last name' I enter 'A Test Last Name'
    And for 'Email' I enter 'atest@example.com'
    And for "Your request" I enter 'A Test description.'
    And I select "No" from "Fee waiver"
    And I select "No" from "Expedited processing"
    And I press the 'Submit' button
    Then I should see the text 'New submission added to A Test Webform.'
    When I am at 'admin/structure/webform/manage/a_test_webform/settings'
    And I click 'Delete'
    And I check the box 'Yes, I want to delete this webform.'
    And I press the 'Delete' button
    Then I should see the following success messages:
      | The webform A Test Webform has been deleted. |

  @api @experimental
  Scenario: Can not delete any or all revisions
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'A Test Agency Component'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component A Test Agency Component has been updated. |
    When I click 'Edit'
    And for 'Agency Component Description' I enter 'change'
    And for 'Revision log message' I enter 'change'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component A Test Agency Component has been updated. |
    When I click 'Revisions'
    Then I should not see 'Delete' in the 'change' row

  @api @agency @experimental
  Scenario: Agency Administrator can add the Agency term references to Agency components
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am at 'admin/structure/taxonomy/manage/agency/add'
    And for 'Name' I enter 'A Test Agency'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Created new term A Test Agency. |
    And I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'A Test Agency Component'
    And for 'Agency' I enter 'A Test Agency'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component A Test Agency Component has been created. |
    And I should see the link 'A Test Agency'

  @api
  Scenario: Agency Administrator can not view admin-related FOIA request pages
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/admin/structure/foia_request"
    Then I should see "Access Denied"
    And I go to "/admin/structure/foia_request/add"
    Then I should see "Access Denied"

  @api @experimental
  Scenario: Agency Administrator can view custom FOIA request view
    Given I am logged in as a user with the 'Administrator' role
    And I am on "/admin/structure/foia_request/add"
    Then I press "Save"
    And save the current URL
    When I am logged in as a user with the 'Agency Administrator' role
    And I am on "/admin/content/foia-requests"
    Then I should see "FOIA Requests"
    And I go to saved URL
    Then I should see "Request Status"

  @api
  Scenario: Agency Administrator can add Annual FOIA Reports
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add"
    Then I should see the link "Annual FOIA Report Data"

  @api
  Scenario: Agency Administrator can save Annual FOIA Reports in all workflow
  states
    Given "agency" terms:
      | name  |field_agency_abbreviation| description |format    | language |
      | test  |DOJ                      | description |plain_text| en       |
    When I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/annual_foia_report_data"
    And for 'Title' I enter 'A Test Report'
    And for 'Agency' I enter 'test'
    And I select "Draft" from "Save as"
    When I press the 'Save' button
    And save the current URL
    Then I should see the following success messages:
      | Annual FOIA Report Data A Test Report has been created. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Submitted to OIP" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data test from manager has been updated. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Cleared" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data test from manager has been updated. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Published" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data test from manager has been updated. |
    When I go to saved URL
    And I click 'Edit'
    And I select "Back with Agency" from "Change to"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data test from manager has been updated. |
