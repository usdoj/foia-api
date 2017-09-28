@personnel
Feature: FOIA Personnel entity
  In order to associate officers, liaisons, and receivers with Agency Components
  As a top-level agency user
  I should be able to update Personnel entities from time to time

  @api
  Scenario: Enter name with 255 characters
    Given I am logged in as a user with the 'add foia personnel entities' permission
    When I am at 'admin/structure/foia_personnel/add'
    And for 'Name' I enter 'MAVIurAaQkyfWwpWImuhpKMePsWzmOmomReikiPzxNMmgTsarAuYfYPBfibQydEBoGpwmMzOANkeRthCFsekqfGGqLLqbEzZNwHnpLLpVQSDribbmHhCaFJZueCDXPoTzpyScWynylUMoHCllEWiJuLFTDrgwcDbbwnHyBzzygLjIDHFmHBvjGnwLxArLZVbBYDxkaCvHvQWYtMjcsBtrbVwCWnDAWUXvssUJKmvOgneNBamyzrvrKImLEqolZf'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Created the MAVIurAaQkyfWwpWImuhpKMePsWzmOmomReikiPzxNMmgTsarAuYfYPBfibQydEBoGpwmMzOANkeRthCFsekqfGGqLLqbEzZNwHnpLLpVQSDribbmHhCaFJZueCDXPoTzpyScWynylUMoHCllEWiJuLFTDrgwcDbbwnHyBzzygLjIDHFmHBvjGnwLxArLZVbBYDxkaCvHvQWYtMjcsBtrbVwCWnDAWUXvssUJKmvOgneNBamyzrvrKImLEqolZf FOIA Personnel. |

  @api
  Scenario: Create, edit, and delete FOIA Personnel entities
    Given I am logged in as a user with the 'Agency Administrator' role
    And I am at 'admin/structure/foia_personnel/add'
    And for 'Name' I enter 'A Test Personnel'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Created the A Test Personnel FOIA Personnel. |
    And I click 'Edit'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Saved the A Test Personnel FOIA Personnel. |
    And I click 'Delete'
    And I press the 'Delete' button
    Then I should see the following success messages:
      | The foia personnel A Test Personnel has been deleted. |
    Given I am logged in as a user with the 'Agency Manager' role
    And I am at 'admin/structure/foia_personnel/add'
    And for 'Name' I enter 'A Test Personnel'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Created the A Test Personnel FOIA Personnel. |
    And I click 'Edit'
    And I press the 'Save' button
    Then I should see the following success messages:
      | Saved the A Test Personnel FOIA Personnel. |
    And I click 'Delete'
    And I press the 'Delete' button
    Then I should see the following success messages:
      | The foia personnel A Test Personnel has been deleted. |
