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
    $wire = $this->requireProcessWire(); // Get ProcessWire or exit

    foreach ($wire->users as $u) {
      $this->write("  {$u->name} [$u]");
    }
    return self::SUCCESS;
  }
}
