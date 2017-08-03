<?php

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site doj, environment dev
$aliases['dev'] = array(
  'root' => '/var/www/html/doj.dev/docroot',
  'ac-site' => 'doj',
  'ac-env' => 'dev',
  'ac-realm' => 'prod',
  'uri' => 'dojdev.prod.acquia-sites.com',
  'remote-host' => 'dojdev.ssh.prod.acquia-sites.com',
  'remote-user' => 'doj.dev',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  )
);
$aliases['dev.livedev'] = array(
  'parent' => '@doj.dev',
  'root' => '/mnt/gfs/doj.dev/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site doj, environment foia
$aliases['foia'] = array(
  'root' => '/var/www/html/doj.foia/docroot',
  'ac-site' => 'doj',
  'ac-env' => 'foia',
  'ac-realm' => 'prod',
  'uri' => 'dojfoia.prod.acquia-sites.com',
  'remote-host' => 'dojfoia.ssh.prod.acquia-sites.com',
  'remote-user' => 'doj.foia',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  )
);
$aliases['foia.livedev'] = array(
  'parent' => '@doj.foia',
  'root' => '/mnt/gfs/doj.foia/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site doj, environment integration
$aliases['integration'] = array(
  'root' => '/var/www/html/doj.integration/docroot',
  'ac-site' => 'doj',
  'ac-env' => 'integration',
  'ac-realm' => 'prod',
  'uri' => 'dojintegration.prod.acquia-sites.com',
  'remote-host' => 'staging-7820.prod.hosting.acquia.com',
  'remote-user' => 'doj.integration',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  )
);
$aliases['integration.livedev'] = array(
  'parent' => '@doj.integration',
  'root' => '/mnt/gfs/doj.integration/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site doj, environment prod
$aliases['prod'] = array(
  'root' => '/var/www/html/doj.prod/docroot',
  'ac-site' => 'doj',
  'ac-env' => 'prod',
  'ac-realm' => 'prod',
  'uri' => 'doj.prod.acquia-sites.com',
  'remote-host' => 'doj.ssh.prod.acquia-sites.com',
  'remote-user' => 'doj.prod',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  )
);
$aliases['prod.livedev'] = array(
  'parent' => '@doj.prod',
  'root' => '/mnt/gfs/doj.prod/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site doj, environment ra
$aliases['ra'] = array(
  'root' => '/var/www/html/doj.ra/docroot',
  'ac-site' => 'doj',
  'ac-env' => 'ra',
  'ac-realm' => 'prod',
  'uri' => 'dojra.prod.acquia-sites.com',
  'remote-host' => 'dojra.ssh.prod.acquia-sites.com',
  'remote-user' => 'doj.ra',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  )
);
$aliases['ra.livedev'] = array(
  'parent' => '@doj.ra',
  'root' => '/mnt/gfs/doj.ra/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site doj, environment stg
$aliases['stg'] = array(
  'root' => '/var/www/html/doj.stg/docroot',
  'ac-site' => 'doj',
  'ac-env' => 'stg',
  'ac-realm' => 'prod',
  'uri' => 'dojstg.prod.acquia-sites.com',
  'remote-host' => 'dojstg.ssh.prod.acquia-sites.com',
  'remote-user' => 'doj.stg',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  )
);
$aliases['stg.livedev'] = array(
  'parent' => '@doj.stg',
  'root' => '/mnt/gfs/doj.stg/livedev/docroot',
);
