<?php

namespace ProcessWire;

/** @var Config $config */
$config->debug = true;
$config->advanced = true;
$config->dbName = 'db';
$config->dbUser = 'db';
$config->dbPass = 'db';
$config->dbHost = 'db';
$config->userAuthSalt = '{userAuthSalt}';
$config->tableSalt = '{tableSalt}';
$config->httpHosts = ['{host}'];
$config->sessionFingerprint = false;

// RockFrontend
$config->livereload = 1;

// RockMigrations
// $config->filesOnDemand = 'https://your-live.site/';
$config->rockmigrations = [
  'syncSnippets' => true,
];

// tracy config for ddev development
$config->tracy = [
  'outputMode' => 'development',
  'guestForceDevelopmentLocal' => true,
  'forceIsLocal' => true,
  'localRootPath' => '/local/path/to/project/',
  'numLogEntries' => 100, // for RockMigrations
];
