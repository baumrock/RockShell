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
    $user = $this->argument('user') ?: 41;
    $u = $this->wire()->users->get("name|id=$user");
    if ($u->id) {
      $u->setAndSave('name', 'ddevadmin');
      $u->setAndSave('pass', 'ddevadmin');
      $this->success("Reset user $u");
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
