<?php

/**
 * @file
 * Includes settings files on Acquia.
 */

use Acquia\Blt\Robo\Common\EnvironmentDetector;

/**
 * @file
 * Site-specific settings.php code for the National FOIA Portal.
 */

if (EnvironmentDetector::isAhEnv()) {
  $ah_group = EnvironmentDetector::getAhGroup();
  $ah_env = EnvironmentDetector::getAhEnv();
  die($ah_env);
  if ($ah_env != 'ide') {
    $additionalSettingsFiles = [
      '/mnt/gfs/home/' . $ah_group . '/' . $ah_env . '/secrets.settings.php',
    ];

    foreach ($additionalSettingsFiles as $settingsFile) {
      if (file_exists($settingsFile)) {
        require $settingsFile;
      }
    }

    if (file_exists(DRUPAL_ROOT . '/sites/acquia.inc')) {
      require_once DRUPAL_ROOT . '/sites/acquia.inc';
      ac_protect_this_site();
    }
  }

  switch ($ah_env) {
    case 'dev':
      $config['samlauth.authentication']['sp_entity_id'] = 'doj_foia_api_dev';
      $config['samlauth.authentication']['idp_single_sign_on_service'] = 'https://login.stage.max.gov/idp/profile/SAML2/Redirect/SSO';
      $config['samlauth.authentication']['idp_entity_id'] = 'https://login.stage.max.gov/idp/shibboleth';
      break;

    case 'test':
      $config['samlauth.authentication']['sp_entity_id'] = 'doj_foia_api_test';
      $config['samlauth.authentication']['idp_single_sign_on_service'] = 'https://login.stage.max.gov/idp/profile/SAML2/Redirect/SSO';
      $config['samlauth.authentication']['idp_entity_id'] = 'https://login.stage.max.gov/idp/shibboleth';
      break;

    case 'uat':
      $config['samlauth.authentication']['sp_entity_id'] = 'doj_foia_api_uat';
      $config['samlauth.authentication']['idp_single_sign_on_service'] = 'https://login.stage.max.gov/idp/profile/SAML2/Redirect/SSO';
      $config['samlauth.authentication']['idp_entity_id'] = 'https://login.stage.max.gov/idp/shibboleth';
      break;

    case 'prod':
      $config['samlauth.authentication']['sp_entity_id'] = 'doj_foia_api_prod';
      $config['samlauth.authentication']['idp_single_sign_on_service'] = 'https://login.max.gov/idp/profile/SAML2/Redirect/SSO';
      $config['samlauth.authentication']['idp_entity_id'] = 'https://login.max.gov/idp/shibboleth';
      break;

  }

  $config['samlauth.authentication']['sp_x509_certificate'] = 'file:/var/www/html/foia.' . $ah_env . '/acquia-files/saml/samlauth_key.pub';
  $config['samlauth.authentication']['sp_private_key'] = 'file:/var/www/html/foia.' . $ah_env . '/acquia-files/saml/samlauth_key';
  $config['samlauth.authentication']['idp_certs'][0] = 'file:/var/www/html/foia.' . $ah_env . '/acquia-files/saml/max_key.pub';
}
