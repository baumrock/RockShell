<?php

namespace RockShell;

use ProcessWire\WireRandom;
use Symfony\Component\Console\Input\InputOption;

class UserReset extends Command
{

  public function config()
  {
    $this
      ->setDescription("Reset username and password")
      ->addOption("user", "u", InputOption::VALUE_OPTIONAL, "old username")
      ->addOption("pass", "p", InputOption::VALUE_OPTIONAL, "new password")
      ->addOption("name", "x", InputOption::VALUE_OPTIONAL, "new name");
  }

  public function handle()
  {
    if (!$user = $this->option('user')) {
      $users = [];
      foreach ($this->wire()->users as $u) $users[] = $u->name;
      $user = $this->choice("Select user", $users);
    }

    $oldname = $user;
    if (!$newname = $this->option('name')) {
      $newname = $this->ask("Enter new name", $oldname);
    }

    if (!$pass = $this->option('pass')) {
      $rand = new WireRandom();
      $pass = $rand->alphanumeric(0, [
        'maxLength' => 20,
        'minLength' => 12,
      ]);
      $pass = $this->ask("Enter Password", $pass);
    }

    $user = $this->wire()->users->get("name=$oldname");
    $user->setAndSave('pass', $pass);
    $user->setAndSave('name', $newname);

    $this->success("$oldname updated: {$user->name} | $pass");
    return self::SUCCESS;
  }
}
