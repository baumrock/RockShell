<?php namespace RockShell;

use Symfony\Component\Console\Input\InputOption;

class DbDump extends Command {

  const backupdir = "/site/assets/backups/database/";

  public function config() {
    $this
      ->setDescription("Create a database dump using PW backup tools")
      ->addOption("file", "f", InputOption::VALUE_OPTIONAL,
        "Filename to be used for the dump inside ".self::backupdir, "db.sql")
      ->addOption("delete", "d", InputOption::VALUE_NONE,
        "Delete all users except guest")
    ;
  }

  public function handle() {
    $wire = $this->wire();
    $file = $this->option("file");

    // delete all users except guest
    if($this->option("delete")) {
      foreach($wire->users as $user) {
        if($user->name == 'guest') continue;
        $this->write("Deleting user {$user->name}...");
        $wire->users->delete($user);
      }
    }

    // backup to setup.sql
    $this->write("Dumping database...");
    $backup = $wire->database->backups();
    $backup->setDatabaseConfig($wire->config);
    $file = $backup->backup(['filename' => $file]);
    if($file) {
      $this->success("Backed up to: $file\n");
    } else {
      $this->success("Backup failed: " . implode("<br>", $backup->errors())."\n");
    }

    return self::SUCCESS;
  }

}
