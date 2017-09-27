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
    And for 'Username' I enter 'Angus'
    And for 'Password' I enter 'abc123!@#'
    And for 'Confirm password' I enter 'abc123!@#'
    And I check the box 'Agency Manager'
    And for 'Agency' I enter 'A Test Agency'
    And I press the 'Create new account' button
    Then I should see the following success messages:
      | Created a new user account for Angus. No email has been sent. |
    When I am at 'admin/people'
    Then I should see 'Agency Manager' in the 'Angus' row
    When I click 'Edit' in the 'Angus' row
    Then I should see the link 'Cancel account'
    When I press the 'Save' button
    Then I should see the following success messages:
      | The changes have been saved. |

  @api
  Scenario: Agency Administrator can not administer user accounts with the Administrator role
    Given users:
      | name  | mail              | roles          |
      | Mini  | mini@example.com  | Administrator  |
    When I am logged in as a user with the 'Agency Administrator' role
    And I am at 'admin/people'
    Then I should see 'Administrator' in the 'Mini' row
    And I should not see 'Edit' in the 'Mini' row
    When I click 'Mini'
    And I click 'Edit'
    Then I should not see a 'Cancel account' link

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
    And I press the 'Save and publish' button
    Then I should see the following success messages:
      | Agency Component A Test Agency Component has been created. |
    And I click 'Edit'
    When I press the 'Save and keep published' button
    Then I should see the following success messages:
      | Agency Component A Test Agency Component has been updated. |
    When I click 'Delete'
    And I press the 'Delete' button
    Then I should see the following success messages:
      | The Agency Component A Test Agency Component has been deleted. |
