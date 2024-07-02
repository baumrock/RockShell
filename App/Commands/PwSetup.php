<?php

namespace RockShell;

class PwSetup extends Command
{

  public function config()
  {
    $this
      ->setDescription("Create an Opinionated PW Project Setup");
  }

  public function handle()
  {
    // add gitignore
    $this->write('Updating .gitignore ...');
    $dst = $this->app->docroot() . ".gitignore";
    $write = true;
    if (is_file($dst)) {
      if (!$this->confirm("$dst exists - overwrite it?", false)) {
        $this->write("Leaving .gitignore as is...");
        $write = false;
      }
    }
    if ($write) {
      $src = $this->stub('.gitignore');
      $this->stubPopulate($src, $dst);
    }

    // add config-local.php
    $localConfig = $this->wire()->config->paths->site . "config-local.php";
    if (!is_file($localConfig) and $this->confirm("Split config into config.php and config-local.php?", true)) {
      $this->warn('Adding config-local.php ...');

      $src = $this->stub('config-local.php');
      $config = $this->wire()->config;
      $this->stubPopulate($src, $localConfig, [
        'host' => $config->httpHost,
        'userAuthSalt' => $config->userAuthSalt,
        'tableSalt' => $config->tableSalt,
      ]);
      $gitignore = $this->app->rootPath() . ".gitignore";
      if (!is_file($gitignore)) {
        file_put_contents($gitignore, "config-local.php\n");
      }

      // update config.php
      $this->write('Updating config.php ...');
      $dst = $this->app->docroot() . "site/config.php";
      $content = file_get_contents($dst);
      $stub = file_get_contents($this->stub('config.php'));
      file_put_contents($dst, $content . $stub);
      $this->removeSalts();
      if ($this->confirm("Remove comments from config.php?", true)) {
        $this->removeComments();
      }
    }

    $this->question("If you want, you can now manually cleanup your files and commit your changes.");
    if ($this->confirm("Continue?", true)) {
      // just wait for confirmation
    } else {
      $this->error("Aborting...");
      exit(1);
    }

    if (!$this->wire()->modules->isInstalled("RockMigrations")) {
      $this->warn("You can now install RockMigrations...");
      $path = $this->wire()->config->paths->root . "site/modules";

      $this->write("To add it as git submodule in a DDEV setup execute this command:");
      $this->question("git submodule add git@github.com:baumrock/RockMigrations.git site/modules/RockMigrations");

      $this->write("");
      $this->write("Alternatively you can download RockMigrations here: "
        . "https://github.com/baumrock/RockMigrations/archive/refs/heads/main.zip");

      if ($this->confirm("Did you download RockMigrations and want to install it?", true)) {
        $this->write("Refreshing modules...");
        $this->wire()->modules->refresh();
        $this->write("Installing RockMigrations...");
        $this->wire()->modules->install("RockMigrations");
      }
    }

    $this->success("Done");
    return self::SUCCESS;
  }

  /**
   * Remove comments from config.php
   * @return void
   */
  public function removeComments()
  {
    $config = $this->app->docroot() . "site/config.php";
    if (!is_file($config)) return $this->error("config.php not found");
    $str = file_get_contents($config);
    $str = preg_replace("#\/\*\*\n([\S\s]*?)\*\/\n#m", "", $str);
    $str = preg_replace("#\n//(.*)\n#m", "", $str);
    file_put_contents($config, $str);
  }

  /**
   * Remove salts from config.php
   * @return void
   */
  public function removeSalts()
  {
    $config = $this->app->docroot() . "site/config.php";
    if (!is_file($config)) return $this->error("config.php not found");
    $str = file_get_contents($config);
    $str = preg_replace("/Installer: Table Salt([\S\s]*?);/m", "Table Salt moved to config-local.php\n */", $str);
    $str = preg_replace("/Installer: User Authentication Salt([\S\s]*?);/m", "User Salt moved to config-local.php\n */", $str);
    file_put_contents($config, $str);
  }
}
