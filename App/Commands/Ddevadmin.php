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
      $name = $u->name;
      $u->setAndSave('name', 'ddevadmin');
      $u->setAndSave('pass', 'ddevadmin');
      $url = $this->wire()->pages->get(2)->httpUrl();
      $this->success("Reset user $name with ID $u");
      $this->write("  New username: ddevadmin");
      $this->write("  New password: ddevadmin");
      $this->write("  Login url: $url");
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
