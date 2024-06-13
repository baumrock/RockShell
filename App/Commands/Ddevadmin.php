<?php

namespace RockShell;

use Symfony\Component\Console\Input\InputArgument;

class Ddevadmin extends Command
{

  public function config()
  {
    $this->setDescription("Reset superuser name+pwd to ddevadmin");
    $this->addArgument('user', InputArgument::OPTIONAL, "User id or name");
  }

  public function handle()
  {
    $user = $this->argument('user');
    $u = $user
      ? $this->wire()->users->get("name=$user")
      : $this->wire()->users->get('roles=superuser');

    if ($u->id) {
      $u->setAndSave('name', 'ddevadmin');
      $u->setAndSave('pass', 'ddevadmin');
      $url = $this->wire()->pages->get(2)->url;
      $this->success("Reset user $u - login url: $url");
    } else {
      $this->warn("User $user not found");
      $users = $this->wire()->pages->find("include=all,template=user,sort=id,limit=50");
      $this->write('Available users:');
      foreach ($users as $u) {
        $this->write($u->id . ": " . $u->name);
      }
    }
    return self::SUCCESS;
  }
}
