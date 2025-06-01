<?php

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * This is a separate file to keep DRY for RockMigrations rockshell() method
 */

require __DIR__ . '/vendor/autoload.php';
$app = new \RockShell\Application();
$app->registerCommands();
$minVersion = '8.0';
if (version_compare(phpversion(), $minVersion, '<')) {
  $output = new ConsoleOutput();
  $output->writeln("<bg=red;options=bold>PHP Version must be at least $minVersion - your version is " . phpversion() . "</>");
  exit(1);
}
$app->run();
