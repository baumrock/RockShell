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
    $wire = $this->requireProcessWire();
    $remote = $this->getRemote();
    $localWireRoot = rtrim($wire->config->paths->root, "/");

    $ssh = $remote->ssh;
    $folder = trim(DbDump::backupdir, "/");

    $this->write("Creating remote dump...");
    $php = $this->option('php') ?: $this->getConfig('remotePHP') ?: 'php';
    $cmd = "$php RockShell/rock db:dump -f tmp.sql";

    $this->write("  Remote rootPath: $remote->rootPath");
    $this->write("  Remote wireRoot: $remote->wireRoot");
    $this->write("  Remote command: $cmd");
    $this->sshExec($ssh, "cd $remote->rootPath && $cmd");

    $this->write("Copying dump to local...");
    $localDump = "{$localWireRoot}/$folder/tmp.sql";
    $this->exec("scp $ssh:{$remote->wireRoot}/$folder/tmp.sql $localDump");

    $this->write("Removing remote dump...");
    $this->sshExec($ssh, "rm -rf {$remote->wireRoot}/$folder/tmp.sql");

    $this->call("db:restore", [
      '--y' => true,
      '--file' => 'tmp.sql',
    ]);

    if (!$this->option('keep')) {
      $this->write("Removing tmp.sql...");
      $this->exec("rm $localDump", false);
      $this->info("Done");
    } else {
      $this->write("Saved dump to tmp.sql");
      $this->info("You can use \"db:restore -f tmp.sql\" to restore again");
    }

    $this->write("");
    return self::SUCCESS;
  }
}
