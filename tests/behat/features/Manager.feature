@manager
Feature: Agency Manager role
  In order to keep my agency components up to date
  As an Agency Manager
  I should be able to edit any agency component that is associated with an agency with which I am associated

  @api
  Scenario: Agency Manager can edit Agency Component with which they are associated
    Given I am logged in as a user with the 'Agency Manager' role and I have the following fields:
      | field_agency | Department of Energy |
    And I am at 'node/4656/edit'
    When I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component National Energy Technology Laboratory has been updated. |

  @api
  Scenario: Agency Manager can not edit Agency Components with which they are not associated
    Given I am logged in as a user with the 'Agency Manager' role and I have the following fields:
      | field_agency | Department of Energy |
    When I am at 'node/5831'
    Then I should not see an 'Edit' link

  @api
  Scenario: Agency Manager can not delete Agency Components
    Given I am logged in as a user with the 'Agency Manager' role and I have the following fields:
      | field_agency | Department of Energy |
    When I am at 'node/4656/edit'
    Then I should not see the button 'Delete'

  @api
  Scenario: Agency Manager can edit taxonomy terms with which they are associated
    Given I am logged in as a user with the 'Agency Manager' role and I have the following fields:
      | field_agency | Department of Energy |
    When I am at 'taxonomy/term/686/edit'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Updated term Department of Energy. |

  @api
  Scenario: Agency Manager can not edit taxonomy terms with which they are not associated
    Given I am logged in as a user with the 'Agency Manager' role and I have the following fields:
      | field_agency | Department of Energy |
    When I am at 'taxonomy/term/591'
    Then I should not see an 'Edit' link

  @api
  Scenario: Agency Manager can not delete taxonomy terms
    Given I am logged in as a user with the 'Agency Manager' role and I have the following fields:
      | field_agency | Department of Energy |
    When I am at 'taxonomy/term/686/edit'
    Then I should not see a 'Delete' link

  @api
  Scenario: Agency Manager can view admin theme
    Given I am logged in as a user with the 'Administrator' role
    When I am at 'admin/people/permissions/agency_manager'
    Then the "View the administration theme" checkbox should be checked

  @api
  Scenario: Agency Manager can not view FOIA requests
    Given I am logged in as a user with the 'Agency Manager' role
    And I am on "/admin/structure/foia_request"
    Then I should see "Access Denied"
    And I go to "/admin/structure/foia_request/add"
    Then I should see "Access Denied"
    And I go to "/admin/content/foia-requests"
    Then I should see "Access Denied"
    When I am logged in as a user with the 'Administrator' role
    And I am on "/admin/structure/foia_request/add"
    Then I press "Save"
    When I am logged in as a user with the 'Agency Manager' role
    And I go to "/admin/structure/foia_request/1"
    Then I should see "Access Denied"
