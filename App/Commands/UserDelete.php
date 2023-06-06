<?php

namespace RockShell;

use Symfony\Component\Console\Input\InputOption;

class UserDelete extends Command
{

  public function config()
  {
    $this
      ->setDescription("Delete a user")
      ->addOption("user", "u", InputOption::VALUE_OPTIONAL, "username");
  }

  public function handle()
  {
    if (!$user = $this->option('user')) {
      $users = [];
      foreach ($this->wire()->users as $u) $users[] = $u->name;
      $user = $this->choice("Select user", $users);
    }

    $user = $this->wire()->users->get("name=$user");
    $this->wire()->users->delete($user);

    $this->success("{$user->name} has been deleted.");
    return self::SUCCESS;
  }
}
