<?php

namespace RockShell;

/**
 * Simple example of a RockShell command
 * See also Ping.php
 */
class Hello extends Command
{

  public function config()
  {
    $this->setDescription("Outputs HELLO WORLD");
  }

  public function handle()
  {
    $this->write("HELLO WORLD");
    return self::SUCCESS;
  }
}
