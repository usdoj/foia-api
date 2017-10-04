<?php

$env = isset($_ENV['AH_SITE_ENVIRONMENT']) ? $_ENV['AH_SITE_ENVIRONMENT'] : '';
$idp = '';

switch ($env) {
  case 'dev':
  case 'test':
    $idp = 'https://login.test.max.gov/idp/shibboleth';
    break;
  case 'prod':
    $idp = 'https://login.max.gov/idp/shibboleth';
    break;
  default:
    $idp = 'https://login.test.max.gov/idp/shibboleth';
}

$config = array(

    // This is an authentication source which handles admin authentication.
    'admin' => array(
      // The default is to use core:AdminPassword, but it can be replaced with
      // any authentication source.

      'core:AdminPassword',
    ),

    // An authentication source which can authenticate against both SAML 2.0
    // and Shibboleth 1.3 IdPs.
    'default-sp' => array(
        'saml:SP',
        'idp' => $idp,
        'NameIDPolicy' => null,
    ),

);
