@administrator
Feature: Agency Administrator role
  In order to keep Agencies, Components, and Managers up to date
  As an Agency Administrator
  I should be able to administer Agency Manager user accounts, agencies, and agency components

  @api
  Scenario: Agency Administrator can administer user accounts with the Agency Manager role
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am at 'admin/structure/taxonomy/manage/agency/add'
    And for 'Name' I enter 'A Test Agency'
    When I press the 'Save' button
    Then I should see the following success messages:
      | Created new term A Test Agency. |
    When I am at 'admin/people/create'
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

  @api
  Scenario: Agency Administrator can not administer user accounts with the (Agency) Administrator or Authenticated roles
    Given users:
      | name   | mail              | roles                |
      | Mini   | mini@example.com  | Administrator        |
      | Angus  | angus@example.com | Agency Administrator |
    When I am logged in as a user with the 'Agency Administrator' role
    And I am at 'admin/people/create'
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
    When I click 'Devel' in the 'Mini' row
    And I click 'View'
    And I attempt to delete the current entity
    Then the response status code should be 404
    When I am at 'admin/people'
    And I click 'Devel' in the 'Angus' row
    And I click 'View'
    And I attempt to delete the current entity
    Then the response status code should be 404
    When I am at 'admin/people'
    And I click 'Devel' in the 'Arthur' row
    And I click 'View'
    And I attempt to delete the current entity
    Then the response status code should be 404
    When I am at 'admin/people'
    And I click 'Devel' in the 'Arthur' row
    And I click 'View'
    And I attempt to delete the current entity
    Then the response status code should be 404

  @api
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

  @api
  Scenario: Agency Administrator can administer Agency Components
    Given I am logged in as a user with the 'Agency Administrator' role
    When I am at 'node/add/agency_component'
    And for 'Agency Component Name' I enter 'A Test Agency Component'
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
  Scenario: Agency Manager can view admin theme
    Given I am logged in as a user with the 'Administrator' role
    When I am at 'admin/people/permissions/agency_administrator'
    Then the "View the administration theme" checkbox should be checked
