#!/usr/bin/env php
<?php
use Symfony\Component\Console\Output\ConsoleOutput;
require __DIR__.'/vendor/autoload.php';
$app = new \RockShell\Application();
$app->registerCommands();
try {
  $app->run();
} catch (\Throwable $th) {
  $output = new ConsoleOutput();
  $output->writeln("<bg=red;options=bold>".$th->getMessage()."</>");
}
