<?php

namespace RockShell;

class Deployment
{
  // by default, we are in debug mode!
  // this ensures that the deployment script will only run hot
  // when the user adds the deploy.php file to the root of the project
  public $debug = true;
  public $indent = 0;
  public $rootPath = '';
  public $wireRoot = '';
  public $current = '';
  public $keep = 2;
  public $newRootPath = '';

  private $php = 'php';
  private $delete = [];
  private $push = [];
  private $share = [];

  public function __construct($argv)
  {
    $logo = file_get_contents(__DIR__ . '/includes/logo.txt');
    $this->echo();
    $this->echo($logo);

    $this->parseArguments($argv);

    $this->headline("Setting up deployment");

    $this->rootPath = $this->normalizePath(dirname(__DIR__));
    $this->echo("Release rootPath: $this->rootPath");
  }

  public function currentPointsTo(string $path): self
  {
    $path = $this->normalizePath($path);
    $this->echo("CurrentPointsTo   $path");
    $this->current = $path;
    $this->wireRoot = $this->rootPath . $path;
    $this->echo("wireRoot:         $this->wireRoot");
    return $this;
  }

  public function delete(string $path): self
  {
    $path = $this->normalizePath($path);
    $this->echo("Delete            $path");
    $this->delete[] = $path;
    return $this;
  }

  public function echo(string $msg = ''): self
  {
    $spaces = str_repeat(' ', $this->indent);
    echo "$spaces$msg\n";
    return $this;
  }

  private function exec(string $cmd): self
  {
    $this->echo(">> shell_exec: $cmd");
    if (!$this->debug) shell_exec($cmd);
    return $this;
  }

  public function exists(string $path): bool
  {
    return file_exists($path);
  }

  private function headline(string $msg): self
  {
    $msg = "// $msg //";
    $len = strlen($msg);
    $this->echo();
    $this->echo();
    $this->echo(str_repeat('-', $len));
    $this->echo($msg);
    $this->echo(str_repeat('-', $len));
    return $this;
  }

  public function mkDir(string $path): self
  {
    $this->echo("Making sure that $path exists");
    if (is_file($path)) {
      $this->echo("Path $path is a file, using parent directory");
      $path = dirname($path);
    }
    $this->exec("mkdir -p $path");
    return $this;
  }

  private function normalizePath(string $path): string
  {
    $path = str_replace('\\', '/', $path);
    $path = ltrim($path, '/');
    if (is_dir($path)) $path = rtrim($path, '/');
    return "/$path";
  }

  public function ok(string $msg, $headline = false): self
  {
    if ($headline) $this->headline("✅ $msg");
    else $this->echo("✅ $msg");
    return $this;
  }

  private function parseArguments($argv)
  {
    $this->headline("Parsing arguments");

    // remove the first argument (the script name)
    $argv = array_slice($argv, 1);

    // loop through the arguments
    $prev = false;
    foreach ($argv as $arg) {
      if ($prev == '--keep' || $prev == '-k') {
        $this->keep = $arg;
        $this->echo("Keep: $this->keep");
      }
      $prev = $arg;
    }

    $this->ok('Done');
  }

  private function passthru(string $cmd): int
  {
    $this->echo(">> passthru: $cmd");
    $returnCode = 0;
    if (!$this->debug) passthru($cmd, $returnCode);
    return $returnCode;
  }

  public function push(string $path): self
  {
    $path = $this->normalizePath($path);
    $this->echo("Push              $path");
    $this->push[] = $path;
    return $this;
  }

  /**
   * Delete given path
   * This will also work for paths like "cache/pwpc-*"
   */
  private function rm(string $path): self
  {
    $this->echo("Deleting $path");
    $fullPath = $this->rootPath . $path;
    if (!$path) {
      $this->echo("No path given, skipping");
      return $this;
    }
    $this->exec("rm -rf $fullPath");
    return $this;
  }

  public function run()
  {
    $this->headline('Running deployment');

    if ($this->debug) $this->echo("debug: ON");
    else $this->echo("debug: OFF");

    $this->runDelete();
    $this->runShare();
    $this->runMigrate();
    $this->runUpdateSymlink();
    $this->runWhenDone();
    $this->runCleanup();

    $this->ok('Deployment complete', true);
  }

  private function runCleanup()
  {
    $this->headline('Cleaning up');
    $this->echo("Keep: $this->keep");
    $this->echo("Found:");
    $releases = array_reverse(glob($this->targetPath() . "/release-*"));
    $keep = $this->keep;
    $first = true;
    foreach ($releases as $r) {
      $keep--;
      if ($keep < 0) {
        $this->echo("  Deleting $r");
        $this->exec("rm -rf $r");
      } else {
        $this->echo("  Keeping $r");
        // rename old folders to flush apache symlink cache
        if (!$first) $this->exec("mv $r $r-");
      }
      $first = false;
    }
    $this->ok('Done');
  }

  private function runDelete()
  {
    $this->headline('Processing deletions');
    foreach ($this->delete as $path) {
      $this->rm($path);
      $this->echo('---');
    }
    $this->ok('Done');
  }

  private function runMigrate()
  {
    $this->headline('Running migrations');
    $file = $this->wireRoot . '/site/modules/RockMigrations/migrate.php';
    if (file_exists($file)) {
      $returnCode = $this->passthru("{$this->php} $file ");
      if ($returnCode !== 0) {
        $this->echo("❌ Migrations failed with return code $returnCode");
        exit(1);
      }
    }
    $this->ok('Done');
  }

  private function runShare()
  {
    $this->headline('Processing shares');

    $targetPath = $this->targetPath() . "/shared";
    $this->echo("Target path: $targetPath");

    $hr = '--------------------------------';
    foreach ($this->share as $path) {
      $this->echo($hr);
      $fullPath = $this->rootPath . $path;
      $type = $this->type($fullPath);

      $this->echo("Sharing $path");
      $this->echo("Type [$type]");
      $this->echo('---');

      // if src does not exist, skip
      if (!$this->exists($fullPath)) {
        $this->echo("Source path $fullPath does not exist, skipping");
        continue;
      }

      // make sure target directory exists
      $target = $targetPath . $path;
      if ($type == "DIRECTORY") $this->mkDir($target);
      else $this->mkDir(dirname($target));
      $this->echo('---');

      // create symlink
      $this->symlink($path);
    }
    $this->echo($hr);
    $this->ok('Done');
  }

  private function runUpdateSymlink()
  {
    $this->headline('Renaming folder');
    $root = $this->rootPath;
    $oldName = basename($root);
    $newName = substr($oldName, 4);
    $parentDir = dirname($root);
    $this->exec("cd $parentDir && mv $oldName $newName");
    $this->ok('Done');
    $this->newRootPath = $parentDir . "/$newName";

    $this->headline('Updating the "current" symlink');
    $from = $this->targetPath() . "/current";
    $this->echo($from);
    $to = $newName;
    $this->echo("  --> $to");
    $this->exec("ln -snf $to $from");
    $this->ok('Done');
  }

  private function runWhenDone()
  {
    $this->headline('Running deploy.whenDone.php');
    $file = $this->newRootPath . '/deploy.whenDone.php';
    if (file_exists($file)) {
      $this->passthru("{$this->php} $file");
    } else {
      $this->echo("File $file does not exist, skipping");
    }
    $this->ok('Done');
  }

  public function setPHP(string $php): self
  {
    $this->php = $php;
    $this->echo("PHP-Command:      $this->php");
    return $this;
  }

  public function share(string $path): self
  {
    $path = $this->normalizePath($path);
    $this->echo("Share             $path");
    $this->share[] = $path;
    return $this;
  }

  private function symlink(string $path): self
  {
    // before creating the symlink copy file/directory to target path
    $from = $this->rootPath . $path;
    $to = $this->targetPath() . "/shared" . $path;
    $type = $this->type($from);

    $copyTo = $type === "DIRECTORY" ? dirname($to) : $to;

    // copy the file or directory to the target path
    $this->echo("Copying");
    $this->echo("  From: $from");
    $this->echo("  To:   $copyTo");
    $this->exec("cp -r $from $copyTo");
    $this->echo('---');

    // create a relative target path for the symlink
    $from = $this->rootPath . $path;
    $depth = substr_count($path, "/");
    $to = "shared" . $path;
    for ($i = 0; $i < $depth; $i++) $to = "../$to";

    // remove the source file/folder and create a symlink to the target path
    $dir = dirname($from);
    $base = basename($from);
    $this->echo("Creating Symlink");
    $this->echo("  From: $from");
    $this->echo("  To:   $to");
    $this->exec("cd $dir && rm -rf $base && ln -snf $to $base");
    return $this;
  }

  private function targetPath(): string
  {
    return $this->normalizePath(dirname($this->rootPath));
  }

  private function type(string $path): string|false
  {
    if (is_dir($path)) return "DIRECTORY";
    if (is_file($path)) return "FILE";
    return "DOES NOT EXIST";
  }
}
