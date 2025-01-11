<?php

namespace RockShell;

use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;

require_once __DIR__ . "/App/Command.php";
class Application extends ConsoleApplication
{

  /**
   * Context variable to share data across commands
   * @var array
   */
  private $context = [];

  private $docroot;

  /**
   * Path to root folder of the project having a trailing slash
   * Use ->rootPath() to access it
   * @var string $root
   */
  private $root;

  public function __construct($name = "RockShell", $version = null)
  {
    if (!$version) {
      $version = json_decode(file_get_contents(__DIR__ . "/package.json"))->version;
    }
    $version .= ' @ PHP' . phpversion();
    $container = new Container;
    $events = new Dispatcher($container);
    parent::__construct($container, $events, $version);
    $this->setName($name);
    $this->root = $this->normalizeSeparators(dirname(__DIR__)) . "/";
    $this->docroot =
      rtrim($this->root . (getenv('DDEV_DOCROOT') ?: getenv('ROCKSHELL_DOCROOT')), "/") . "/";
  }

  /**
   * Add command from file
   * @return void
   */
  public function addCommandFromFile($file)
  {
    if (!is_file($file)) return;
    require_once($file);
    $name = pathinfo($file, PATHINFO_FILENAME);
    $root = $this->root . "RockShell/App/Commands/";
    if (strpos($file, $root) === 0) {
      $namespace = "\RockShell";
    } else {
      // get namespace from module name:
      // /site/modules/FooModule/RockShell/Commands/FooCommand.php
      // would be namespace "FooModule"
      $namespace = basename(dirname(dirname(dirname($file))));
    }
    $class = "$namespace\\$name";
    $command = new $class();
    $command->app = $this;
    $this->add($command);
  }

  /**
   * Get or set context variable
   * @param string name
   * @param mixed $data
   * @return mixed
   */
  public function context($name, $data = null)
  {
    if (!$data) {
      if (!array_key_exists($name, $this->context)) return false;
      return $this->context[$name];
    }
    $this->context[$name] = $data;
  }

  public function docroot()
  {
    return $this->docroot;
  }

  /**
   * Find all command files in the current project
   *
   * This will look for commands in
   * /RockShell/App/Commands/
   * /site/modules/RockShell/Commands
   * /site/assets/RockShell/Commands
   *
   * It will also take care of loading the base command from /RockShell/Command.php
   *
   * @return array
   */
  public function findCommandFiles()
  {
    $roots = [
      $this->root . "RockShell/App/",
      $this->docroot . "site/modules",
      $this->docroot . "site/assets",
    ];
    $files = array();
    foreach ($roots as $root) {
      if (!is_dir($root)) continue;
      $directory = new \RecursiveDirectoryIterator(
        $root,
        \FilesystemIterator::FOLLOW_SYMLINKS
      );
      $iterator = new \RecursiveIteratorIterator($directory);
      foreach ($iterator as $info) {
        if ($info->getExtension() !== 'php') continue;
        $filename = $this->normalizeSeparators($info->getPathname());

        // skip some files and folders
        if (strpos($info->getFilename(), ".") === 0) continue;
        if (strpos($filename, "/export-profile/")) continue;
        if (strpos($filename, "/vendor/")) continue;
        if (strpos($filename, "/lib/")) continue;
        if (strpos($filename, "/tracy-")) continue;
        if (!strpos($filename, "/RockShell/")) continue;
        if (strpos($filename, '/site/modules/.')) continue;

        // if we find a new base command file we load it now
        // see readme about adding new base-commands to your project
        if (strpos($filename, "/Command.php")) require_once($filename);

        // add commandfiles to array
        if (strpos($filename, "/Commands/")) $files[] = $filename;
      }
    }
    return $files;
  }

  /**
   * Given a path, normalize it to "/" style directory separators if they aren't already
   * @static
   * @param string $path
   * @return string
   */
  public static function normalizeSeparators($path)
  {
    if (DIRECTORY_SEPARATOR == '/') return $path;
    $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
    return $path;
  }

  /**
   * Register commands from array of files
   * @return void
   */
  public function registerCommands($files = null, $ns = null)
  {
    $files = $files ?: $this->findCommandFiles();
    foreach ($files as $file) $this->addCommandFromFile($file, $ns);
  }

  /**
   * @return string
   */
  public function rootPath()
  {
    return $this->root;
  }
}
