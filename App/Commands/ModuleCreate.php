<?php

namespace RockShell;

use Symfony\Component\Console\Input\InputOption;

use function ProcessWire\wire;

/**
 * Demonstrates different output styles
 * See also Hello.php
 */
class ModuleCreate extends Command
{

  public function config()
  {
    $this->setDescription("Create a new module with all necessary files")
      ->addOption('name', null, InputOption::VALUE_OPTIONAL, "Module name");
  }

  private function copyFiles($name): void
  {
    $src = __DIR__ . '/../stubs/module-create';
    $dst = wire()->config->paths->siteModules . $name;
    $replace = [
      "MyModule" => $name,
      "mymodule" => strtolower($name),
    ];
    wire()->files->mkdir($dst);
    wire()->files->copy(
      $src . '/.github',
      $dst . '/.github',
    );
    wire()->files->copy(
      $src . '/package.json',
      $dst . '/package.json',
    );
    $this->stubPopulate(
      $src . '/MyModule.info.php',
      $dst . "/$name.info.php",
      $replace,
      quiet: true,
      brackets: false,
    );
    $this->stubPopulate(
      $src . '/MyModule.module.txt',
      $dst . "/$name.module.php",
      $replace,
      quiet: true,
      brackets: false,
    );
  }

  public function handle()
  {
    $types = ['Module', 'Process Module', 'Fieldtype Module', 'Inputfield Module'];
    $type = $this->choice('Type of module', $types, 0);
    if ($type !== 'Module') {
      $this->warn("Sorry, not implemented yet. What a great opportunity for a PR!");
      return $this->handle();
    }
    $name = $this->moduleName();
    $this->copyFiles($name);
    return self::SUCCESS;
  }

  private function moduleName($name = null, $reset = false)
  {
    if (!$name) $name = $this->option('name');
    if (!$name || $reset) $name = $this->ask("Please enter your module's name");
    $name = ucfirst(wire()->sanitizer->camelCase($name));

    if (!$name) return $this->moduleName(reset: $reset);

    if (!$this->confirm("Confirm module name: $name", true)) {
      return $this->moduleName();
    }

    // check if it exists
    $dir = wire()->config->paths->siteModules . $name;
    if (is_dir($dir)) {
      $this->warn("$dir already exists");

      if ($this->confirm("Create module in this folder? This will overwrite existing files.")) {
        return $name;
      }

      return $this->moduleName(reset: true);
    }

    return $name;
  }
}
