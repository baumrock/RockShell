<?php

namespace RockShell;

/**
 * Debug command
 */
class Debug extends Command
{

  public function config()
  {
    $this->setDescription("Shows debug information");
  }

  public function handle()
  {
    $this->write('PHP-Version: ' . phpversion());
    $this->write('rootPath()');
    $this->write('  ' . $this->app->rootPath());
    $this->write('wireRoot()');
    $this->write('  ' . $this->app->wireRoot());
    return self::SUCCESS;
  }
}
