@saml
Feature: SimpleSAML PHP Authentication
  In order to federate authentication
  As an authenticated government user
  I should be able to log in to the FOIA system with my single sign on credentials

  @api
  Scenario: Ensure that the SimpleSAMLphp library is installed and configured as a Service Provider
    Given I am at 'simplesaml'
    Then I should get a 200 HTTP response
