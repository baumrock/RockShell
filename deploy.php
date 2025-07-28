<?php

namespace RockShell;

chdir(__DIR__);
require_once 'vendor/autoload.php';

$deploy = (new Deployment($argv))
  ->currentPointsTo('public')
  # delete some files and folders
  ->delete('public/site/assets/cache')
  ->delete('public/site/assets/ProCache')
  ->delete('public/site/assets/pwpc-*')
  // share some files and folders
  ->share('public/site/assets/files')
  ->share('public/site/assets/backups')
  ->share('public/site/assets/logs')
  ->share('public/site/assets/sessions')
  // push some files and folders
  // ->push('public/site/assets/files/123') // german translations
  // ->push('public/site/assets/files/456') // french translations
  // done
;

if (file_exists('../deploy.php')) require_once '../deploy.php';

$deploy->run();
