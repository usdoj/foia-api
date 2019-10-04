@saml
Feature: SimpleSAML PHP Authentication
  In order to federate authentication
  As an authenticated government user
  I should be able to log in to the FOIA system with my single sign on credentials

  @api @experimental
  Scenario: Ensure that the SimpleSAMLphp library is installed and configured as a Service Provider
    Given I am at 'simplesaml'
    Then I should get a 200 HTTP response

  @api
  Scenario: Terms of service
    Given I am at 'user/login'
    Then I should see "You are accessing a U.S. Government information system, which includes: (1) this computer, (2) this computer network, (3) all computers connected to this network, and (4) all devices and storage media attached to this network or to a computer on this network. This information system is provided for U.S. Government-authorized use only. Unauthorized or improper use of this system may result in disciplinary action, and civil and criminal penalties."
    And I should see "By logging in to this information system you are acknowledging that you understand and consent to the following:"
    And I should see "- You have no reasonable expectation of privacy regarding any communications transmitted through or data stored on this information system. At any time, the government may monitor, intercept, search and/or seize data transiting or stored on this information system."
    And I should see "- Any communications transmitted through or data stored on this information system may be disclosed or used for any U.S. Government-authorized purpose."
    And I should see "For further information see the Department order on Use and Monitoring of Department Computers and Computer Systems."
