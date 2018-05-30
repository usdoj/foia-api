<?php

/**
 * @file
 * Site-specific settings.php code for the National FOIA Portal.
 */

if (file_exists(DRUPAL_ROOT . '/sites/acquia.inc')) {
  require DRUPAL_ROOT . '/sites/acquia.inc';
  ac_protect_this_site();
}