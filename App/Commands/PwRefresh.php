<?php

namespace RockShell;

class PwRefresh extends Command
{
  public function config()
  {
    $this
      ->setDescription("Trigger a modules::refresh");
  }

  public function handle()
  {
    $wire = $this->requireProcessWire(); // Get ProcessWire or exit
    $wire->modules->refresh();
    return self::SUCCESS;
  }
}
