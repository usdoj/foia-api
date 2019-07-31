@manager @experimental
Feature: Agency Manager role
  In order to keep my agency components up to date
  As an Agency Manager
  I should be able to edit any agency component that is associated with an agency with which I am associated

  Background:
    Given users:
      | name | mail          | roles          | field_agency         |
      | Tess | t@example.com | Agency Manager | Department of Energy |
    Given agency_component content:
      | title                   | field_agency               |
      | A Test Agency Component | Department of Energy       |
      | B Test Agency Component | Tennessee Valley Authority |

  @api
  Scenario: Agency Manager can edit Agency Component with which they are associated
    Given I am logged in as "Tess"
    And I go to the "node" type entity with the "A Test Agency Component" label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component National Energy Technology Laboratory has been updated. |

  @api
  Scenario: Agency Manager can not edit Agency Components with which they are not associated
    Given I am logged in as "Tess"
    When I go to the "node" type entity with the "B Test Agency Component" label
    Then I should not see an 'Edit' link

  @api
  Scenario: Agency Manager can not delete Agency Components
    Given I am logged in as "Tess"
    When I go to the "node" type entity with the "A Test Agency Component" label
    Then I should not see the button 'Delete'

  @api
  Scenario: Agency Manager can edit taxonomy terms with which they are associated
    Given I am logged in as "Tess"
    When I go to the "taxonomy_term" type entity with the "Department of Energy" label
    And I edit the current entity
    And I press the 'Save' button
    Then I should see the following success messages:
      | Updated term Department of Energy. |

  @api
  Scenario: Agency Manager can not edit taxonomy terms with which they are not associated
    Given I am logged in as "Tess"
    When I go to the "taxonomy_term" type entity with the "Tennessee Valley Authority" label
    Then I should not see an 'Edit' link

  @api
  Scenario: Agency Manager can not delete taxonomy terms
    Given I am logged in as "Tess"
    When I go to the "taxonomy_term" type entity with the "Department of Energy" label
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
    And save the current URL
    When I am logged in as a user with the 'Agency Manager' role
    And I go to saved URL
    Then I should see "Access Denied"
