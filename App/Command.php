<?php

namespace RockShell;

use Exception;
use Illuminate\Console\Command as ConsoleCommand;
use LogicException;
use ProcessWire\ProcessWire;
use ProcessWire\User;
use ReflectionClass;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends ConsoleCommand
{

  /**
   * Reference to the application
   * @var Application
   */
  public $app;

  /** @var HttpBrowser */
  protected $browser;

  /** @var InputInterface */
  protected $input;

  /** @var OutputInterface */
  protected $output;

  /** @var ReflectionClass */
  protected $reflect;

  /** @var  */
  private $wire;

  public function __construct($name = null)
  {
    $this->reflect = new ReflectionClass($this); // very first!
    $this->name = $this->name($name);
    parent::__construct($this->name);
    if (method_exists($this, 'config')) $this->config();
  }

  /**
   * Print backtrace (for debugging)
   * @return void
   */
  public function backtrace()
  {
    $trace = debug_backtrace();
    echo "--- backtrace ---\n";
    foreach ($trace as $item) {
      echo "  " . $item['file'] . ":" . $item['line'] . "\n";
    }
    echo "------------------\n";
  }

  /**
   * Convert string to colonCase
   * Converts FooBar to foo-bar
   * Taken from PW Sanitizer
   * @return string
   */
  public static function colonCase($value, array $options = array())
  {

    $defaults = array(
      'hyphen' => ':',
      'allow' => 'a-z0-9',
      'allowUnderscore' => false,
    );

    $options = array_merge($defaults, $options);
    $value = (string)$value;
    $hyphen = $options['hyphen'];

    // if value is empty then exit now
    if (!strlen($value)) return '';

    if ($options['allowUnderscore']) $options['allow'] .= '_';

    // check if value is already in the right format, and return it if so
    if (strtolower($value) === $value) {
      if ($options['allow'] === $defaults['allow']) {
        if (ctype_alnum(str_replace($hyphen, '', $value))) return $value;
      } else {
        if (preg_match('/^[' . $hyphen . $options['allow'] . ']+$/', $value)) return $value;
      }
    }

    // don’t allow apostrophes to be separators
    $value = str_replace(array("'", "’"), '', $value);
    // some initial whitespace conversions to reduce workload on preg_replace
    $value = str_replace(array(" ", "\r", "\n", "\t"), $hyphen, $value);
    // convert everything not allowed to hyphens
    $value = preg_replace('/[^' . $options['allow'] . ']+/i', $hyphen, $value);
    // convert camel case to hyphenated
    $value = preg_replace('/([[:lower:]])([[:upper:]])/', '$1' . $hyphen . '$2', $value);
    // prevent doubled hyphens
    $value = preg_replace('/' . $hyphen . $hyphen . '+/', $hyphen, $value);

    if ($options['allowUnderscore']) {
      $value = str_replace(array('-_', '_-'), '_', $value);
    }

    return strtolower(trim($value, $hyphen));
  }

  /**
   * Check if a .ddev folder exists in pw root
   * @return bool
   */
  public function ddev()
  {
    return $this->ddevExists() and is_dir("/var/www/html");
  }

  /**
   * Check if a ddev folder exists
   * @return bool
   */
  public function ddevExists()
  {
    return is_dir($this->app->rootPath() . ".ddev");
  }

  /**
   * Get name of the folder where the current command file lives
   * @return string
   */
  public function dirName()
  {
    return basename(dirname($this->reflect->getFileName()));
  }

  /**
   * Execute php command via php's exec()
   *
   * Usage:
   * $this->exec("cd /foo/bar
   *   touch test1.txt
   *   touch test2.txt
   * ");
   */
  public function exec($cmd, $output = true)
  {
    // replace newlines by && to join multiline commands
    $cmd = str_replace("\n", " && ", $cmd);
    if ($this->output->isVerbose()) $this->write($cmd);
    exec($cmd, $out);
    if ($this->output->isVerbose() or $output) $this->write($out);
    return $out;
  }

  /**
   * Overwrite the symfony commands execute() method and proxy it to handle()
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;
    $this->sudo();
    return $this->handle();
  }

  /**
   * Filter dom for current selector and print output to console
   * @return void
   */
  public function filter($selector)
  {
    echo $this->browser->getCrawler()->filter($selector)->outerHtml();
  }

  /**
   * Get config from $config->rockshell
   * @return mixed
   */
  public function getConfig($prop = null, $quiet = true)
  {
    $config = $this->wire()->config->rockshell;
    if (!$config) return false;
    if ($prop) {
      if (array_key_exists($prop, $config)) return $config[$prop];
      else {
        if ($quiet) return false;
        else return $this->error("Property '$prop' not found in config");
      }
    }
    return $config;
  }

  public function goodbye(): void
  {
    // return a random goodbye message
    $goodbyeMessages = [
      "Have a nice day!",
      "Goodbye!",
      "See you later!",
      "Take care!",
      "Catch you later!",
      "Farewell!",
      "Bye for now!",
      "Until next time!",
      "Peace out!",
      "Stay safe!",
      "Adios!",
      "Ciao!",
      "Sayonara!",
      "Au revoir!",
      "Toodle-oo!",
      "Cheerio!",
      "Later, alligator!",
      "After a while, crocodile!",
      "Keep it real!",
      "Stay classy!",
      "Happy coding!",
      "May the force be with you!",
      "Keep calm and code on!",
      "May your code be bug-free!",
      "404: Goodbye message not found! No worries, it's a joke ;)",
      "See you on the flip side!",
      "Don't let the semicolon bite you!",
      "Keep on hacking!",
      "May your coffee be strong and your code be short!",
      "Happy debugging!",
      "Keep pull requests coming!",
      "May your code compile on the first try!",
      "Stay DRY!",
      "Keep your code clean!",
      "May your functions be pure and your variables immutable!",
      "Don't let the bugs bite you!",
      "Keep your codebase tidy!",
      "May your tests always pass!",
      "May your code be as efficient as a well-oiled machine!",
      "Don't forget to take breaks!",
      "Keep your code simple and your logic sound!",
      "May your algorithms be swift and your data structures robust!"
    ];
    // return a random goodbye message
    $this->write($goodbyeMessages[array_rand($goodbyeMessages)]);
  }

  /**
   * Execute this command
   */
  public function handle()
  {
    throw new LogicException('You must override the handle() method in your command.');
  }

  /**
   * Check if domelement has given class
   */
  public function hasClass($el, $class)
  {
    $classes = explode(" ", $el->getAttribute('class'));
    return in_array($class, $classes);
  }

  /**
   * Print the current html to console in interactive mode
   * @return void
   */
  public function html()
  {
    echo $this->browser->getInternalResponse()->getContent();
  }

  public function isCLI(): bool
  {
    return php_sapi_name() === 'cli';
  }

  /**
   * Build name of current command
   */
  public function name($name)
  {
    $name = $name ?: $this->shortName();
    $dir = $this->dirName();
    $namespace = $dir === 'Commands' ? '' : strtolower($dir) . ":";
    return $namespace . $name;
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
   * Pause execution of command and enter interactive mode
   * @return void
   */
  public function pause($vars = [])
  {
    $this->warn("\nEntering interactive shell...");
    $this->write("You can now execute any PHP command");
    $this->write("The current command is available as \$this");

    extract($vars);
    foreach ($vars as $k => $v) {
      $data = $v;
      if (is_object($data)) $data = class_basename($data);
      $this->write("  \$$k = $data");
    }

    $history = [];
    while ($cmd = $this->askWithCompletion("PHP", $history)) {
      $history[] = $cmd;
      array_reverse($history);
      ob_start();
      try {
        eval($cmd);
      } catch (\Throwable $th) {
        echo $th->getMessage();
      }
      $out = ob_get_clean();
      if ($out) $this->write("$out\n");
    }
  }

  /**
   * Generate a random string, using a cryptographically secure
   * pseudorandom number generator (random_int)
   *
   * For PHP 7, random_int is a PHP core function
   * For PHP 5.x, depends on https://github.com/paragonie/random_compat
   *
   * @param int $length      How many characters do we want?
   * @param string $keyspace A string of all possible characters
   *                         to select from
   * @return string
   */
  function randomStr($length = 0, $keyspace = null)
  {
    if (!$keyspace) $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    if (!$length) $length = rand(10, 15);
    $str = '';
    $max = mb_strlen($keyspace, '8bit') - 1;
    if ($max < 1) {
      throw new Exception('$keyspace must be at least two characters long');
    }
    for ($i = 0; $i < $length; ++$i) {
      $str .= $keyspace[random_int(0, $max)];
    }
    return $str;
  }

  /**
   * Return path of RockShell folder for current command
   * @return string
   */
  public function shellPath()
  {
    $path = $this->reflect->getFileName();
    while (basename($path) !== 'RockShell') $path = dirname($path);
    return "$path/";
  }

  /**
   * Get short classname of this command
   * @return string
   */
  public function shortName()
  {
    return $this->colonCase($this->reflect->getShortName());
  }

  /**
   * Execute command on remote via ssh
   * @return void
   */
  public function sshExec($ssh, $cmd, $echo = false)
  {
    $cmd = str_replace("\n", " && ", $cmd);
    if ($echo) $this->write($cmd);
    $this->exec("ssh $ssh \"$cmd\"");
  }

  /**
   * Convert to string
   * @return string
   */
  public function str($data)
  {
    if (is_string($data)) return $data;
    if (is_array($data)) {
      $out = '';
      foreach ($data as $line) $out .= "  $line\n";
      return $out;
    }
    ob_start();
    var_dump($data);
    return ob_get_clean();
  }

  /**
   * Get path for stub file
   * Does not check if the file exists!
   * @return string
   */
  public function stub($file)
  {
    // the RockShell module has an App folder
    // all other modules do not have this folder
    if (is_dir($this->shellPath() . "App"))
      return $this->shellPath() . "App/stubs/$file";
    else
      return $this->shellPath() . "stubs/$file";
  }

  /**
   * Populate stub placeholders
   * @return void
   */
  public function stubPopulate(
    $src,
    $dst,
    $vars = [],
    $quiet = false,
    $brackets = "{}"
  ) {
    $src = realpath($src);
    $content = file_get_contents($src);
    if (!$quiet) {
      $this->write("Writing $src");
      $this->write("  to $dst");
    }
    $left = @$brackets[0];
    $right = @$brackets[1];
    foreach ($vars as $k => $v) {
      $content = str_replace($left . $k . $right, $v, $content);
    }
    file_put_contents($dst, $content);
  }

  /**
   * Write success message to output
   */
  public function success($str)
  {
    $this->info($this->str($str));
  }

  /**
   * Make RockShell run as superuser
   * @return void
   */
  public function sudo(): void
  {
    if (!$this->wire()) return;
    // this will create a new superuser at runtime
    // this ensures that we can run rockshell without superusers on the system
    // it also ensures that the user has the default language set which is
    // important to avoid hard to find bugs where scripts set values
    // in non-default languages
    $su = new User();
    $su->addRole("superuser");
    $this->wire()->users->setCurrentUser($su);
  }

  /**
   * Enforce trailing slash and normalize separators
   * @param string $path
   * @return string
   */
  public function trailingSlash($path)
  {
    return rtrim($this->normalizeSeparators($path), "/") . "/";
  }

  /**
   * Get wire instance
   * @return ProcessWire|false
   */
  public function wire()
  {
    if ($this->wire) return $this->wire;
    if ($this->wire === false) return;
    chdir($this->app->docroot());

    // pw is not yet there, eg when using pw:install
    if (!is_file("index.php")) return false;

    // pw is here but not installed
    if (is_file("install.php")) {
      $this->alert("ProcessWire exists but is not installed");
      $this->wire = false;
      return false;
    }

    try {
      include 'index.php';
      return $this->wire = $wire;
    } catch (\Throwable $th) {
      echo $th->getMessage() . "\n";
      return false;
    }
  }

  /**
   * Write string to output
   */
  public function write($str)
  {
    $str = $this->str($str);
    $this->output->writeln($str);
  }
}
