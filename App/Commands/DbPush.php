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

class DbPush extends Command
{
  private $remote;
  private $dbName;
  private $dbUser;
  private $dbPass;

  public function config()
  {
    $this
      ->setDescription("Push a database dump to a remote server")
      ->addArgument("remote", InputArgument::OPTIONAL)
      ->addOption("dbName", "", InputOption::VALUE_OPTIONAL)
      ->addOption("dbUser", "", InputOption::VALUE_OPTIONAL)
      ->addOption("dbPass", "", InputOption::VALUE_OPTIONAL)
      ->addOption("debug", "d", InputOption::VALUE_NONE)
      ->addOption("reset", "r", InputOption::VALUE_NONE)
      ->addOption("fast", "f", InputOption::VALUE_NONE);
  }

  public function handle()
  {
    $this->requireProcessWire();
    $this->remote = $this->getRemote();
    return $this->setupCredentials();
  }

  /** --------------------------- helper methods --------------------------- */

  private function askForReset()
  {
    $this->error("Database has tables");
    $this->warn("Do you want to reset the database?");
    $this->warn("This will delete all tables and data");
    $this->warn("This is irreversible");
    $reset = $this->option("reset") ?: $this->confirm("Reset the database?", false);
    if ($reset) return $this->dropAllTables();
    else {
      $this->error("Database reset aborted");
      return self::SUCCESS;
    }
  }

  private function checkEmpty()
  {
    // check if there are any tables in the database
    $this->warn("\nChecking if database is empty");
    $cmd = "mysql -u $this->dbUser -p$this->dbPass $this->dbName -e 'SHOW TABLES;'";
    $result = $this->sshExec($this->remote->ssh, $cmd);
    $result = trim((string)@$result[0]);
    if ($result) return $this->askForReset();
    else {
      $this->success("Database is empty\n");
      $this->call("db:dump", [
        '--file' => '.DbPush.sql',
      ]);
      return $this->uploadDB();
    }
  }

  private function cleanup()
  {
    $this->warn("\nRemoving db dump file");
    $file = "{$this->remote->dir}/.DbPush.sql";
    $this->sshExec($this->remote->ssh, "rm $file");
    $this->success("Db dump file removed");
    return self::SUCCESS;
  }

  private function dropAllTables()
  {
    $this->warn("\nDropping all tables");

    // Get all table names
    $cmd = "mysql -u $this->dbUser -p$this->dbPass $this->dbName -e 'SHOW TABLES;' -s --skip-column-names";
    $tables = $this->sshExec($this->remote->ssh, $cmd);

    if (!is_array($tables)) {
      $this->error("Failed to get table names");
      return self::FAILURE;
    }

    // drop all tables at once or one by one?
    $fast = $this->option("fast") ?: $this->confirm("Drop all tables at once?", false);
    if ($fast) {
      $cmd = '';
      foreach ($tables as $table) {
        $table = trim($table);
        $cmd .= "DROP TABLE IF EXISTS $table;";
      }
      $cmd = "mysql -u $this->dbUser -p$this->dbPass $this->dbName -e '$cmd'";
      $this->sshExec($this->remote->ssh, $cmd);
    } else {
      foreach ($tables as $table) {
        $table = trim($table);
        $this->write("Dropping table: $table");
        $cmd = "mysql -u $this->dbUser -p$this->dbPass $this->dbName -e 'DROP TABLE IF EXISTS $table;'";
        $this->sshExec($this->remote->ssh, $cmd);
      }
    }
    return $this->checkEmpty();
  }

  private function restoreDB()
  {
    $this->warn("\nRestoring database");
    $cmd = "mysql -u $this->dbUser -p$this->dbPass $this->dbName < {$this->remote->dir}/.DbPush.sql";
    $this->sshExec($this->remote->ssh, $cmd);
    $this->success("Database restored");
    return $this->cleanup();
  }

  private function setupCredentials()
  {
    // ask for db credentials
    $this->warn("Setting up connection to the remote database");
    $this->dbName = $this->option("dbName")
      ?: $this->remote->dbName
      ?: $this->ask("Enter the database name");
    $this->dbUser = $this->option("dbUser")
      ?: $this->remote->dbUser
      ?: $this->ask("Enter the database user", $this->dbName);
    $this->dbPass = $this->option("dbPass")
      ?: $this->remote->dbPass
      ?: $this->ask("Enter the database password");
    $this->write("  dbName: $this->dbName");
    $this->write("  dbUser: $this->dbUser");
    $this->write("  dbPass: ***");
    $this->success("Database credentials set");
    return $this->testConnection();
  }

  private function testConnection()
  {
    // try to connect to the remote database
    $this->warn("\nTesting connection");
    $cmd = "mysql -u $this->dbUser -p$this->dbPass $this->dbName -e 'SELECT 1;'";
    // $cmd = "mysqladmin -u $dbUser -p$dbPass ping";
    // if ($this->option("debug")) $this->write($cmd);
    $result = $this->sshExec($this->remote->ssh, $cmd);
    $result = (int)@$result[0];
    if ($result === 1) {
      $this->success("Connection successful");
      return $this->checkEmpty();
    } else {
      $this->error("Connection failed");
      return self::FAILURE;
    }
  }

  private function uploadDB()
  {
    $this->warn("Uploading database");

    // get file to upload
    $file = $this->wire()->config->paths->assets . "backups/database/.DbPush.sql";
    if (!file_exists($file)) {
      $this->write($file);
      $this->error("Database file not found");
      return self::FAILURE;
    }

    // upload file
    $this->system("rsync --progress $file {$this->remote->ssh}:{$this->remote->dir}");
    $this->success("Database uploaded");

    return $this->restoreDB();
  }
}
