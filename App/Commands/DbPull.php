<?php

namespace RockShell;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * $config->rockshell = [
 *   'remotes' => [
 *     'staging' => [
 *       'ssh' => 'user@yourserver.com',
 *       'dir' => '/path/to/pw/root',
 *     ],
 *   ],
 * ];
 */

class DbPull extends Command
{
  const backupdir = "/site/assets/backups/database/";

  public function config()
  {
    $this
      ->setDescription("Pull a database dump from remote server")
      ->addArgument("remote",         InputArgument::OPTIONAL)
      ->addOption("keep",       "k",  InputOption::VALUE_NONE,      "Keep tmp.sql after restore")
      ->addOption("php",        "p",  InputOption::VALUE_OPTIONAL,  "PHP command to use, eg keyhelp-php81");
  }

  public function handle()
  {
    $wire = $this->requireProcessWire(); // Get ProcessWire or exit
    $remote = $this->getRemote();

    $ssh = $remote->ssh;
    $dir = rtrim($remote->dir, "/");
    $folder = trim(DbDump::backupdir, "/");

    $this->write("Creating remote dump...");
    $php = $this->option('php') ?: $this->getConfig('remotePHP') ?: 'php';
    $cmd = "$php RockShell/rock db:dump -f tmp.sql";

    $this->write("  Remote path: $dir");
    $this->write("  Remote command: $cmd");
    $this->sshExec($ssh, "cd $dir && $cmd");

    $this->write("Copying dump to local...");
    $this->exec("scp $ssh:$dir/$folder/tmp.sql {$wire->config->paths->root}$folder/tmp.sql");

    $this->write("Removing remote dump...");
    $this->sshExec($ssh, "cd $dir && rm -rf $dir/$folder/tmp.sql");

    $this->call("db:restore", [
      '--y' => true,
      '--file' => 'tmp.sql',
    ]);

    if (!$this->option('keep')) {
      $this->write("Removing tmp.sql...");
      $this->exec("rm $folder/tmp.sql", false);
      $this->info("Done");
    } else {
      $this->write("Saved dump to tmp.sql");
      $this->info("You can use \"db:restore -f tmp.sql\" to restore again");
    }

    $this->write("");
    return self::SUCCESS;
  }
}
