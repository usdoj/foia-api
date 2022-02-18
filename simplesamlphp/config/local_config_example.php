<?php

/*
This is an example file for overriding the SimpleSAML configuration for
local testing. To use this, copy this file to "local_config.php" which
is an unversioned file and can be edited according to your needs.
*/

// Local database settings for SimpleSAML - in this example for DDEV.
$config['store.type'] = 'sql';
$config['store.sql.dsn'] = sprintf('mysql:host=%s;port=%s;dbname=%s', 'ddev-foia-api-db', '3306', 'db');
$config['store.sql.username'] = 'db';
$config['store.sql.password'] = 'db';
