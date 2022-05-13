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
  if ($ah_env == 'ide') {
    $config['simplesamlphp_auth.settings']['activate'] = false;
  }
  else {
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
}
