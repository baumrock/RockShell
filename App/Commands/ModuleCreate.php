<?php

namespace RockShell;

use Symfony\Component\Console\Input\InputOption;

use function ProcessWire\wire;

/**
 * Create a new module with all necessary files
 */
class ModuleCreate extends Command
{
  use Concerns\RequiresProcessWire;

  public function config()
  {
    $this->setDescription("Create a new module with all necessary files")
      ->addOption('name', null, InputOption::VALUE_OPTIONAL, "Module name");
  }

  private function copyFiles($name, $wire): void
  {
    $src = __DIR__ . '/../stubs/module-create';
    $dst = $wire->config->paths->siteModules . $name;
    $replace = [
      "MyModule" => $name,
      "mymodule" => strtolower($name),
    ];
    $wire->files->mkdir($dst);
    $wire->files->copy(
      $src . '/.github',
      $dst . '/.github',
    );
    $wire->files->copy(
      $src . '/package.json',
      $dst,
    );
    $wire->files->copy(
      $src . '/.htaccess',
      $dst,
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
    $this->stubPopulate(
      $src . '/readme.md',
      $dst . "/readme.md",
      $replace,
      quiet: true,
      brackets: false,
    );
  }

  private function getDir($name, $wire): string
  {
    return $wire->config->paths->siteModules . $name;
  }

  public function handle()
  {
    $wire = $this->requireProcessWire(); // Get ProcessWire or exit
    
    $types = ['Module', 'Process Module', 'Fieldtype Module', 'Inputfield Module'];
    $type = $this->choice('Type of module', $types, 0);
    if ($type !== 'Module') {
      $this->warn("Sorry, not implemented yet. What a great opportunity for a PR!");
      return $this->handle();
    }
    $name = $this->moduleName($wire);
    $this->copyFiles($name, $wire);

    $dir = $this->getDir($name, $wire);
    $this->success("Module created at $dir");
    $this->goodbye();

    return self::SUCCESS;
  }

  private function moduleName($wire, $name = null, $reset = false)
  {
    if (!$name) $name = $this->option('name');
    if (!$name || $reset) $name = $this->ask("Please enter your module's name");
    $name = ucfirst($wire->sanitizer->camelCase($name));

    if (!$name) return $this->moduleName($wire, reset: $reset);

    if (!$this->confirm("Confirm module name: $name", true)) {
      return $this->moduleName($wire);
    }

    // check if it exists
    $dir = $this->getDir($name, $wire);
    if (is_dir($dir)) {
      $this->warn("$dir already exists");

      if ($this->confirm("Create module in this folder? This will overwrite existing files.")) {
        return $name;
      }

      return $this->moduleName($wire, reset: true);
    }

    return $name;
  }
}
