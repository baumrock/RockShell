<?php

namespace RockShell;

use Symfony\Component\Console\Input\InputOption;

class UserDelete extends Command
{
  use Concerns\RequiresProcessWire;

  public function config()
  {
    $this
      ->setDescription("Delete a user")
      ->addOption("user", "u", InputOption::VALUE_OPTIONAL, "username");
  }

  public function handle()
  {
    $wire = $this->requireProcessWire(); // Get ProcessWire or exit

    if (!$user = $this->option('user')) {
      $users = [];
      foreach ($wire->users as $u) $users[] = $u->name;
      $user = $this->choice("Select user", $users);
    }

    $user = $wire->users->get("name=$user");
    $wire->users->delete($user);

    $this->success("{$user->name} has been deleted.");
    return self::SUCCESS;
  }
}
