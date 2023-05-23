<?php

namespace RockShell;

class PwUsers extends Command
{

  public function config()
  {
    $this
      ->setDescription("List all users");
  }

  public function handle()
  {
    foreach ($this->wire()->users as $u) {
      $this->write("  {$u->name} [$u]");
    }
    return self::SUCCESS;
  }
}
