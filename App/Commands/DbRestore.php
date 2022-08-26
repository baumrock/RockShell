<?php namespace RockShell;

use ProcessWire\User;
use Symfony\Component\Console\Input\InputOption;

class DbRestore extends Command {

  const backupdir = "/site/assets/backups/database/";

  public function config() {
    $this
      ->setDescription("Restore a database dump using PW backup tools")
      ->addOption("file",           "f",  InputOption::VALUE_OPTIONAL,  "Filename to be used for the restore inside ".self::backupdir, "db.sql")
      ->addOption("dropAll",        "d",  InputOption::VALUE_OPTIONAL,  "Drop all tables before restore", true)
      ->addOption("y",              "y",  InputOption::VALUE_NONE,      "Don't ask for confirmation")
      ->addOption("add-superuser",  "a",  InputOption::VALUE_NONE,      "Add superuser after restore")
      ->addOption("name",           null, InputOption::VALUE_OPTIONAL,  "Name of superuser")
      ->addOption("pass",           "p",  InputOption::VALUE_OPTIONAL,  "Password of superuser")
      ->addOption("migrate",        "m",  InputOption::VALUE_NONE,      "Run migrations after restore")
    ;
  }

  public function handle() {
    $wire = $this->wire();
    $file = $this->option("file");

    // confirm
    if(!$this->option("y") AND
      !$this->confirm("Do you really want to restore the db from file $file?")) {
      $this->write("Aborting...");
      return self::SUCCESS;
    }

    // restore db
    if($this->option("dropAll")) $this->write("Dropping of all tables enabled.");
    $this->write("Restoring DB from $file ...");
    $backup = $wire->database->backups();
    $backup->setDatabaseConfig($wire->config);
    $success = $backup->restore($file, ['dropAll' => $this->option("dropAll")]);
    if($success) $this->success("Done\n");
    else {
      $this->error("Restore failed: " . implode("<br>", $backup->errors())."\n");
      return self::FAILURE;
    }

    // on ddev we reset all logs
    // otherwise tracy shows old logs as if they were new ones on first reload
    if($this->ddev() AND $wire->config->debug) {
      $this->write("Removing old logs...");
      $files = glob($wire->config->paths->logs.'*');
      foreach($files as $file) {
        if(is_file($file)) unlink($file);
      }
    }

    // on ddev we reset the superuser
    $su = $wire->users->get($wire->config->superUserPageID);
    if($this->ddev() AND $wire->config->debug) {
      if(!$su OR !$su->id) {
        $this->write("Adding superuser...");
        $su = $wire->wire(new User());
        $su->id = $wire->config->superUserPageID;
      }
      else $this->write("Resetting superuser...");
      $admin = 'ddevadmin';
      $su->name = $admin;
      $su->pass = $admin;
      $su->roles->add($wire->roles->get("name=superuser"));
      $su->save();
      $this->success("New superuser: username = $admin, password = $admin");
    }

    // check for ddev user on non-debug systems
    if(!$wire->config->debug AND $su->name == 'ddevadmin') {
      $su->setAndSave('pass', $this->randomStr());
      $this->warn("WARNING: Superuser name 'ddevadmin' on system with debug mode OFF...");
      $this->warn("Password was reset to a random string!\n");
    }

    // run migrations?
    if($this->option('y') OR $this->option("migrate")
      OR $this->confirm("Do you want to run migrations now?")) {
      $this->warn("\nRunning migrations...");
      $this->exec("php site/modules/RockMigrations/migrate.php");
    }

    // show login message
    $this->warn("Login URL of your site: ".$wire->pages->get(2)->httpUrl);

    $this->write("");
    return self::SUCCESS;
  }

}
