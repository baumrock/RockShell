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

class DbDownload extends Command
{

  const backupdir = "/site/assets/backups/database/";

  public function config()
  {
    $this
      ->setDescription("Download a database dump from remote server")
      ->addArgument("remote",         InputArgument::OPTIONAL)
      ->addOption("list",       "l",  InputOption::VALUE_NONE,      "List remotes")
      ->addOption("php",        "p",  InputOption::VALUE_OPTIONAL,  "PHP command to use, eg keyhelp-php81");
  }

  public function handle()
  {
    $wire = $this->wire();

    // get remotes from config
    $remotes = $this->getConfig('remotes');
    if (!$remotes or !count($remotes)) {
      $this->error("No remotes defined in \$config->rockshell['remotes'] - see DbPull.php for help");
      return self::FAILURE;
    }

    if ($this->option("list")) {
      $this->info("Available remotes:");
      $this->write($remotes);
    }

    // if remote is not specified and we only have one remote
    // we take that one to pull from
    if (!$this->argument("remote") and count($remotes) === 1) {
      foreach ($remotes as $remote) {
        $remote = (object)$remote;
      }
    } else {
      $remoteName = $this->argument("remote") ?: $this->choice("Choose remote", array_keys($remotes));
      $remote = (object)$remotes[$remoteName];
    }

    $ssh = $remote->ssh;
    $dir = rtrim($remote->dir, "/");
    $folder = trim(DbDump::backupdir, "/");

    $this->write("Creating remote dump ...");
    $php = $this->option('php') ?: $this->getConfig('remotePHP') ?: 'php';
    $this->sshExec($ssh, "cd $dir
      $php RockShell/rock db:dump -f tmp.sql", true);

    $this->write("Copying dump to local ...");
    $this->exec("scp $ssh:$dir/$folder/tmp.sql {$wire->config->paths->root}$folder/tmp.sql");

    $this->write("Removing remote dump ...");
    $this->sshExec($ssh, "cd $dir && rm -rf $dir/$folder/tmp.sql");

    $this->write("Download complete!\n");

    return self::SUCCESS;
  }
}
