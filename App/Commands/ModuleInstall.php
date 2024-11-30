<?php

namespace RockShell;

use Symfony\Component\Console\Input\InputOption;

use function ProcessWire\wire;

/**
 * Install a module
 */
class ModuleInstall extends Command
{

  public function config()
  {
    $this->setDescription("Install a module")
      ->addOption('name', 'm', InputOption::VALUE_OPTIONAL, "Module name");
  }

  public function handle()
  {
    // check name
    $name = $this->option('name');
    while (!$name) $name = $this->ask("Please enter the module's name");

    wire()->modules->refresh();

    // special case for RockMigrations
    if ($name == 'RockMigrations') {
      $rm = wire()->modules->get('RockMigrations');
      if (!$rm) {
        $this->error("RockMigrations module not found");
        return self::FAILURE;
      }
      return self::SUCCESS;
    }

    // load RockMigrations
    $rm = wire()->modules->get('RockMigrations');
    if (!$rm) {
      $this->error("RockMigrations module not found");
      return self::FAILURE;
    }

    // install module
    $rm->installModule($name);

    return self::SUCCESS;
  }
}
