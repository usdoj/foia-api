@administrator
Feature: Agency Administrator role
  In order to keep Agencies, Components, Reports, and Managers up to date
  As an Agency Administrator
  I should be able to administer Agency Manager user accounts, agencies,
  agency components, and Annual FOIA Reports

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
      | Success messages                                               |
      | Created a new user account for Arthur. No email has been sent. |
    When I am at 'admin/people'
    Then I should see 'Administrator' in the 'Mini' row
    And I should not see 'Edit' in the 'Mini' row
    And I should see 'Agency Administrator' in the 'Angus' row
    And I should not see 'Edit' in the 'Angus' row
    And I should not see 'Edit' in the 'Arthur' row
    And I view the user 'Mini'
    And I attempt to delete the current entity
    Then I should see "Page not found"
    When I am at 'admin/people'
    And I view the user 'Angus'
    And I attempt to delete the current entity
    Then I should see "Page not found"
    When I am at 'admin/people'
    And I view the user 'Arthur'
    And I attempt to delete the current entity
    Then I should see "Page not found"
    And the user 'Arthur' is deleted

  @api @agency
  Scenario: Agency Administrator can administer Agencies
    Given I am logged in as a user with the 'Agency Administrator' role
    When I am at 'admin/structure/taxonomy/manage/agency/add'
    And for 'Name' I enter 'A Test Agency'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Success messages                |
      | Created new term A Test Agency. |
    And I am at 'admin/structure/taxonomy/manage/agency/overview'
    When I click 'Edit' in the 'A Test Agency' row
    Then I should see the link 'Delete'
    When I press the 'Save' button
    Then I should see the following success messages:
      | Success messages            |
      | Updated term A Test Agency. |

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
    Given "agency" terms:
      | name  |field_agency_abbreviation| description |format    | language |
      | test  |DOJ                      | description |plain_text| en       |
    And I am logged in as a user with the 'Agency Administrator' role
    When I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'A Test Agency Component'
    And for 'Agency' I enter 'test'
    And for Abbreviation I enter 'TAC'
    And for 'Street address' I enter '123'
    And for 'City' I enter '123'
    And I select "Louisiana" from "State"
    And for 'Zip code' I enter '12345'
    And I uncheck the box "Published"
    And I press the 'Save' button
    Then I should see the following success messages:
      | Success messages                                           |
      | Agency Component A Test Agency Component has been created. |

  @api
  Scenario: Agency Administrator can not view admin-related FOIA request pages
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am on "/admin/structure/foia_request"
    Then I should see "Access Denied"
    And I go to "/admin/structure/foia_request/add"
    Then I should see "Access Denied"

  @api
  Scenario: Non Agency Administrator cannot see Report Start and Expiration
  Dates
    When I am logged in as a user with the 'Agency Component creator' role
    And I am on "/node/add/agency_component"
    Then I should not see "Report Start Date"
    And I should not see "Report Expiration Date"

  @api
  Scenario: Agency Administrator can update Report Start and Expiration Dates
    When I am logged in as a user with the 'Agency Administrator' role
    And I am on "/node/add/agency_component"
    Then I should see "Report Start Date"
    And I should see "Report Expiration Date"
