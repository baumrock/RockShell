<?php

use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * This is a separate file to keep DRY for RockMigrations rockshell() method
 */

require __DIR__ . '/vendor/autoload.php';
$app = new \RockShell\Application();
$app->registerCommands();
try {
  if (version_compare(phpversion(), '8.0', '<')) {
    throw new Exception(
      "PHP Version must be at least 8.0 - your version is " . phpversion()
    );
  }
  $app->run();
} catch (\Throwable $th) {
  $output = new ConsoleOutput();
  $output->writeln("<bg=red;options=bold>" . $th->getMessage() . "</>");
}
