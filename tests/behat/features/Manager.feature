@manager
Feature: Agency Manager role
  In order to keep my agency components up to date
  As an Agency Manager
  I should be able to edit any agency component that is associated with an agency with which I am associated

#  Background:
#    Given users:
#      | name | mail          | roles          | field_agency         |
#      | Tess | t@example.com | Agency Manager | Department of Energy |
#    Given agency_component content:
#      | title                   | field_agency               |
#      | A Test Agency Component | Department of Energy       |
#      | B Test Agency Component | Tennessee Valley Authority |

  @api @experimental
  Scenario: Agency Manager can edit Agency Component with which they are associated
    Given I am logged in as "Tess"
    And I go to the "node" type entity with the "A Test Agency Component" label
    And I edit the current entity
    When I press the 'Save' button
    Then I should see the following success messages:
      | Agency Component National Energy Technology Laboratory has been updated. |

  @api @experimental
  Scenario: Agency Manager can not edit Agency Components with which they are not associated
    Given I am logged in as "Tess"
    When I go to the "node" type entity with the "B Test Agency Component" label
    Then I should not see an 'Edit' link

  @api @experimental
  Scenario: Agency Manager can not delete Agency Components
    Given I am logged in as "Tess"
    When I go to the "node" type entity with the "A Test Agency Component" label
    Then I should not see the button 'Delete'

  @api @experimental
  Scenario: Agency Manager can edit taxonomy terms with which they are associated
    Given I am logged in as "Tess"
    When I go to the "taxonomy_term" type entity with the "Department of Energy" label
    And I edit the current entity
    And I press the 'Save' button
    Then I should see the following success messages:
      | Updated term Department of Energy. |

  @api @experimental
  Scenario: Agency Manager can not edit taxonomy terms with which they are not associated
    Given I am logged in as "Tess"
    When I go to the "taxonomy_term" type entity with the "Tennessee Valley Authority" label
    Then I should not see an 'Edit' link

  @api @experimental
  Scenario: Agency Manager can not delete taxonomy terms
    Given I am logged in as "Tess"
    When I go to the "taxonomy_term" type entity with the "Department of Energy" label
    Then I should not see a 'Delete' link

  @api @experimental
  Scenario: Agency Manager can view admin theme
    Given I am logged in as a user with the 'Administrator' role
    When I am at 'admin/people/permissions/agency_manager'
    Then the "View the administration theme" checkbox should be checked

  @api @experimental
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

  @api
  Scenario: Agency Manager can add Annual FOIA Reports
    Given I am logged in as a user with the 'Agency Manager' role
    And I am on "/node/add/annual_foia_report_data"
    Then I should see text matching "Create Annual FOIA Report Data"

  @api
  Scenario: Agency Manager can save Annual FOIA Reports as Draft
    Given "agency" terms:
      | name  |field_agency_abbreviation| description |format    | language |
      | test  |DOJ                      | description |plain_text| en       |
    When I am logged in as a user with the 'Agency Manager' role
    And I am on "/node/add/annual_foia_report_data"
    And for 'Title' I enter 'A Test Report'
    And for 'Agency' I enter 'test'
    And I select "Draft" from "Save as"
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data A Test Report has been created. |

  @api
  Scenario: Agency Manager can save Annual FOIA Reports as Submitted to OIP
    Given "agency" terms:
      | name  |field_agency_abbreviation| description |format    | language |
      | test  |DOJ                      | description |plain_text| en       |
    When I am logged in as a user with the 'Agency Manager' role
    And I am on "/node/add/annual_foia_report_data"
    And for 'Title' I enter 'A Test Report'
    And for 'Agency' I enter 'test'
    And I select "Submitted to OIP" from "Save as"
    When I press the 'Save' button
    Then I should see the following success messages:
      | Annual FOIA Report Data A Test Report has been created. |
