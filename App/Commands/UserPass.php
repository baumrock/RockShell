<?php

namespace RockShell;

use ProcessWire\WireRandom;
use Symfony\Component\Console\Input\InputOption;

class UserPass extends Command
{

  public function config()
  {
    $this
      ->setDescription("Reset password of a user")
      ->addOption("user", "u", InputOption::VALUE_OPTIONAL, "username");
  }

  public function handle()
  {
    if (!$user = $this->option('user')) {
      $users = [];
      foreach ($this->wire()->users as $u) $users[] = $u->name;
      $user = $this->choice("Select user", $users);
    }

    $rand = new WireRandom();
    $pass = $rand->alphanumeric(0, [
      'maxLength' => 20,
      'minLength' => 12,
    ]);
    $pass = $this->ask("Enter Password", $pass);

    $user = $this->wire()->users->get("name=$user");
    $user->setAndSave('pass', $pass);

    $this->success("Password set for user " . $user->name . ".");
    return self::SUCCESS;
  }
}
