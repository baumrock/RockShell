<?php

namespace RockShell;

use Symfony\Component\Console\Input\InputOption;

class UserRename extends Command
{

  public function config()
  {
    $this
      ->setDescription("Rename a user")
      ->addOption("user", "u", InputOption::VALUE_OPTIONAL, "name of existing user")
      ->addOption("name", "r", InputOption::VALUE_OPTIONAL, "new username");
  }

  public function handle()
  {
    $users = [];
    foreach ($this->wire()->users as $u) $users[] = $u->name;

    if (!$user = $this->option('user')) {
      $user = $this->choice("Select user", $users);
    }

    $name = $this->option('name');
    $name = $this->wire()->sanitizer->pageName($name);
    while (!$name or in_array($name, $users)) {
      $name = $this->ask("Enter new username");
      $name = $this->wire()->sanitizer->pageName($name);
    }

    $user = $this->wire()->users->get("name=$user");
    $this->success("User {$user->name} renamed to $name");
    $user->setAndSave('name', $name);

    return self::SUCCESS;
  }
}
