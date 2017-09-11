<?php

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site foia, environment dev.
$aliases['dev'] = array(
  'root' => '/var/www/html/foia.dev/docroot',
  'ac-site' => 'foia',
  'ac-env' => 'dev',
  'ac-realm' => 'prod',
  'uri' => 'foiadev.prod.acquia-sites.com',
  'remote-host' => 'foiadev.ssh.prod.acquia-sites.com',
  'remote-user' => 'foia.dev',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  ),
);
$aliases['dev.livedev'] = array(
  'parent' => '@foia.dev',
  'root' => '/mnt/gfs/foia.dev/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site foia, environment test.
$aliases['test'] = array(
  'root' => '/var/www/html/foia.test/docroot',
  'ac-site' => 'foia',
  'ac-env' => 'test',
  'ac-realm' => 'prod',
  'uri' => 'foiastg.prod.acquia-sites.com',
  'remote-host' => 'foiastg.ssh.prod.acquia-sites.com',
  'remote-user' => 'foia.test',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  ),
);
$aliases['test.livedev'] = array(
  'parent' => '@foia.test',
  'root' => '/mnt/gfs/foia.test/livedev/docroot',
);
