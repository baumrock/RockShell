<?php

namespace RockShell;

/**
 * Demonstrates different output styles
 * See also Hello.php
 */
class Ping extends Command
{

  public function config()
  {
    $this->setDescription("Executes a ping command to processwire.com");
  }

  public function handle()
  {
    $this->system("ping -c 4 processwire.com");
    return self::SUCCESS;
  }
}
