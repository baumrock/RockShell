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
$config->livereloadBuild = true;

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
  'localRootPath' => getenv("TRACY_LOCALROOTPATH"),
  'numLogEntries' => 100, // for RockMigrations
  // 'editor' => 'cursor://file/%file:%line',
];
