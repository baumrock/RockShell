<?php

namespace RockShell;

use Env\Env;

/**
 * Demonstrates how to read .env file with ENV class
 */
class EnvTest extends Command
{

  public function config()
  {
    $this->setDescription("Test ENV class by FireWire");
  }

  public function handle()
  {
    $this->write("Reading .env file from " . __DIR__);
    $env = Env::load(__DIR__);
    $this->info("Value of foo: " . $env->get("foo"));
    return self::SUCCESS;
  }
}
