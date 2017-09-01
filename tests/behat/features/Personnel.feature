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
