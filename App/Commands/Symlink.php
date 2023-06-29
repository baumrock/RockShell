<?php

namespace RockShell;

class Symlink extends Command
{

  public function config()
  {
    $this->setDescription("Creates a rockshell symlink in the PW root folder");
  }

  public function handle()
  {
    $root = $this->wire()->getRootPath();
    $this->exec("cd $root && ln -snf RockShell/rockshell rockshell", false);
    $dst = "{$root}rockshell";
    if (is_file($dst)) $this->success("Symlink created at $dst");
    else $this->error("Error creating symlink at $dst");
    return self::SUCCESS;
  }
}
