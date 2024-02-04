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
    $this->setDescription("Outputs 'pong' in different styles");
  }

  public function handle()
  {
    $this->output->writeln("pong: writeln()");
    $this->write("pong: write()"); // shortcut of above
    $this->info("pong: info()");
    $this->error("pong: error()");
    $this->comment("pong: comment()");
    $this->question("pong: question()");
    return self::SUCCESS;
  }
}
