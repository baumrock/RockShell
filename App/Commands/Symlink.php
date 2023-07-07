<?php

namespace RockShell;

/**
 * Create a symlink to be able to call RockShell directly from the pw root directory.
 * We call the symlink "rock" because RockShell is already taken by the folder
 * and that leads to conflicts on case-insensitive file systems.
 */
class Symlink extends Command
{

  public function config()
  {
    $this->setDescription("Creates a symlink to RockShell in the PW root folder");
  }

  public function handle()
  {
    $root = $this->wire()->getRootPath();
    $this->exec("cd $root && ln -snf RockShell/rockshell rock", false);
    if (is_file($root . "rock")) {
      $this->success("Symlink 'rock' created at $root");
      $this->comment("You can now use 'php rock' to execute RockShell");
    } else $this->error("Error creating 'rock' symlink at $root");
    return self::SUCCESS;
  }
}
