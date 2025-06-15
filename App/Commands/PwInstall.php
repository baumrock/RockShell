<?php

namespace RockShell;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpClient\HttpClient;

class PwInstall extends Command
{

  /** @var HttpBrowser */
  protected $browser;

  private $host;

  private $skipWelcome = false;
  private $skipNextConfirm = false;

  private $stepCount = 0;

  public function config()
  {
    $this
      ->setDescription("Install ProcessWire")
      ->addOption("host", null, InputOption::VALUE_REQUIRED, "Hostname of your new site")
      ->addOption('profile', null, InputOption::VALUE_REQUIRED, "Site-Profile to install")
      ->addOption('debug', 'd', InputOption::VALUE_NONE, "Enable debug mode")
      ->addOption('step', null, InputOption::VALUE_REQUIRED, "Step")
      ->addOption('timezone', 't', InputOption::VALUE_REQUIRED, "Timezone (int or string)")
      ->addOption('remove', 'r', InputOption::VALUE_NONE)
      ->addOption('ignore', 'i', InputOption::VALUE_NONE)
      ->addOption('url', 'u', InputOption::VALUE_REQUIRED, "Url of the backend")
      ->addOption('name', null, InputOption::VALUE_REQUIRED, "Name of superuser")
      ->addOption('pass', 'p', InputOption::VALUE_REQUIRED, "Password of superuser")
      ->addOption('mail', 'm', InputOption::VALUE_REQUIRED, "Mail-Address of superuser")
      ->addOption("dev", null, InputOption::VALUE_NONE, "Download dev version of pw?");
  }

  public function handle()
  {
    if ($this->ddevExists() and !$this->ddev()) {
      $this->error("Use ddev ssh to execute this command from within DDEV");
      return self::FAILURE;
    }

    if ($this->wire()) {
      $this->alert("ProcessWire is already installed!");
      return self::SUCCESS;
    }

    $this->browser = new HttpBrowser(HttpClient::create());
    $this->nextStep(true);
    return self::SUCCESS;
  }

  /** ##### steps ##### */

  public function nextStep($reload = false, $noConfirm = false)
  {
    $this->stepCount = $this->stepCount + 1;
    if ($this->stepCount > 50) return; // hard limit to prevent endless loops

    // if a reload is required we fire another request
    if ($reload) $this->browser->request('GET', $this->host('install.php'));

    // get current step from headline
    $step = $this->getStep();
    if (!$step) return false;
    if (is_array($step)) return $this->warn($this->str($step));

    // show errors
    $this->browser->getCrawler()->filter("div.uk-alert")
      ->each(function (Crawler $el) {
        $this->warn($el->text());
      });

    // execute the step
    $method = "step" . ucfirst($step);
    $next = $this->$method();
    if ($this->option('debug')) {
      $this->warn("Step $step (debug mode) - entering interactive shell");
      $this->pause([
        'browser' => $this->browser,
      ]);
    }

    // next step?
    // if the last step returned false we break
    if ($next !== false) {
      if ($this->skipNextConfirm || $noConfirm) return $this->nextStep();
      if ($this->confirm("Continue to next step?", true)) return $this->nextStep();
    }
  }

  public function stepWelcome()
  {
    if (!$this->skipWelcome) $this->write('Welcome');
    $this->browser->submitForm('Get Started');
  }

  public function stepProfile()
  {
    // offer to download the rockfrontend site profile
    $zip = 'https://github.com/baumrock/site-rockfrontend/releases/latest/download/site-rockfrontend.zip';
    $exists = is_dir("site-rockfrontend");
    if (file_exists('site-rockfrontend.zip')) $this->exec('rm site-rockfrontend.zip');
    if (!$exists && $this->confirm("Download RockFrontend Site Profile?", true)) {
      $this->write('Downloading ...');
      $this->exec("wget --quiet $zip");
      $this->write('Extracting files ...');
      $this->exec('unzip -q site-rockfrontend.zip');
      $this->nextStep(true, true);      return;
    }

    $this->newLine();
    $this->write("Install site profile ...");

    $profiles = $this->browser
      ->getCrawler()
      ->filter('select[name=profile] > option')
      ->extract(['value']);
    if (!count($profiles)) {
      $this->error("No profiles found - aborting ...");
      die();
    }
    $profiles = array_values(array_filter($profiles));
    if (!$profile = $this->option('profile')) {
      $profile = $this->choice(
        "Select profile to install",
        $profiles,
        in_array('site-rockfrontend', $profiles) ? 'site-rockfrontend' : $profiles[0]
      );
    }
    $this->write("Using profile $profile ...");
    $this->browser->submitForm("Continue", [
      'profile' => $profile,
    ]);
  }

  public function stepCompatibility()
  {
    $this->write("Checking compatibility ...");
    $errors = 0;
    $this->browser
      ->getCrawler()
      ->filter('div.uk-section-muted > div.uk-container > div')
      ->each(function (Crawler $el) use (&$errors) {
        $text = $el->text();
        $outer = $el->outerHtml();
        if (strpos($outer, 'fa-check')) $this->success($text);
        else {
          $errors++;
          $this->warn($text);
        }
      });
    if ($errors) {
      if ($this->confirm("Check again?", true)) {
        $this->skipWelcome = true;
        $this->skipNextConfirm = true;
        return $this->nextStep(true);
      } else {
        if ($this->confirm("Continue Installation?", false)) {
          $this->skipNextConfirm = true;
          $this->browser->submitForm('Continue to Next Step');
        } else {
          $this->warn('Aborting ...');
          die();
        }
      }
    } else {
      $this->skipNextConfirm = false;
      $this->browser->submitForm('Continue to Next Step');
    }
  }
  public function stepDatabase()
  {
    $this->newLine();
    $this->write("Setting the following sections:");
    $this->browser->getCrawler()->filter('h2')->each(function (Crawler $el) {
      $this->write("  " . $el->text());
    });
    $form = $this->fillForm([
      'dbName' => 'db',
      'dbPass' => 'db',
      'dbUser' => 'db',
      'dbHost' => 'db',
      'dbCharset' => 'utf8mb4',
      'dbEngine' => 'InnoDB',
      'debugMode' => 0, // will be enabled in config-local.php
    ]);
    $this->browser->submitForm('Continue', $form->getValues());
  }  public function stepAdmin()
  {
    $this->write("Setup admin panel and user");
    $form = $this->fillForm([
      'admin_name' => $this->option('url') ?: 'processwire',
      'username' => $this->option('name') ?: 'ddevadmin',
      'userpass' => $this->option('pass') ?: 'ddevadmin',
      'useremail' => $this->option('mail'),
    ]);
    if ($this->output->isVeryVerbose()) var_dump($form->getValues());
    $this->browser->submitForm("Continue", $form->getValues());
  }

  public function stepFinish()
  {
    $this->success("Finishing installation ...");
    $this->writeNotes();
    return $this->stepReloadAdmin();
  }

  public function stepReloadAdmin($notice = true)
  {
    if ($notice) $this->info("\nLoading ProcessWire ...");
    chdir($this->app->docroot());
    include "index.php";
    /** @var ProcessWire $wire */
    $url = $this->host($wire->pages->get(2)->url);
    if ($notice) $this->write($url);
    $this->browser->request('GET', $url);

    $notices = 0;
    $this->browser->getCrawler()
      ->filter("li.NoticeMessage")
      ->each(function (Crawler $el) use (&$notices) {
        $notices++;
        $this->write($el->text());
      });
    if ($notices) {
      $this->warn("Reloading ...");
      return $this->stepReloadAdmin(false);
    } else {
      $this->success("\n"
        . "##### INSTALL SUCCESSFUL ######\n"
        . "### powered by baumrock.com ###\n");
      $this->warn("Login: $url");
      die();
    }
  }

  /** ##### end steps ##### */

  /**
   * Normalize a URL: remove :443 for https and :80 for http
   */
  private function normalizeUrl($url) {
    $parts = parse_url($url);
    if (!$parts || !isset($parts['scheme'], $parts['host'])) return $url;

    $scheme = $parts['scheme'];
    $host = $parts['host'];
    $port = $parts['port'] ?? null;
    $path = $parts['path'] ?? '';

    $isDefaultPort = ($scheme === 'https' && ($port == 443 || $port === null)) ||
                     ($scheme === 'http' && ($port == 80 || $port === null));

    if (!$isDefaultPort && $port) {
        return "$scheme://$host:$port$path";
    }

    return "$scheme://$host$path";
}


  /**
   * Cleans and normalizes the httpHosts array
   */
  private function cleanHttpHostsArray($hosts): array {
    if (is_string($hosts)) {
      $hosts = preg_split('/[\s,]+/', $hosts);
    }
    $hosts = array_map('trim', $hosts);
    $hosts = array_filter($hosts);

    $seen = [];
    $cleaned = [];

    foreach ($hosts as $host) {
      // extract bare host if port is 80/443
      if (preg_match('/^(.+):(443|80)$/', $host, $m)) {
        $bare = $m[1];
        // if we've already seen the bare host, skip
        if (isset($seen[$bare])) continue;
        // mark this host as seen but don't add yet
        if (!isset($seen[$host])) $seen[$host] = 'skip';
        continue;
      }

      // if it's a bare host and we've seen the port version, override
      $seen[$host] = 'add';
      $cleaned[] = $host;
    }

    // Add port-specific hosts if their bare host hasn't been added
    foreach ($seen as $host => $action) {
      if ($action === 'skip') $cleaned[] = $host;
    }

    return array_values(array_unique($cleaned));
  }



  /**
   * @return Form
   */
  public function fillForm($defaults = [])
  {
    $form = $this->browser->getCrawler()->filter('.InputfieldForm');
    if (!$form->count()) return $this->error('No form found');
    $form = $form->form();
    $values = $form->getPhpValues();
    $pass = '';

    $skipAll = false;
    foreach ($values as $name => $val) {
      if ($skipAll) continue;
      // if the value is set via option we take this value
      try {
        $val = $this->option($name);
      } catch (\Throwable $th) {
      }

      // otherwise we continue and ask the user for input
      $options = [];
      $field = $form[$name];
      $default = $val;
      if (array_key_exists($name, $defaults)) $default = $defaults[$name];

      if ($this->output->isVeryVerbose()) $this->warn($name);
      if ($field instanceof ChoiceFormField) {
        $this->browser
          ->getCrawler()
          ->filter("input[name=$name],select[name=$name] > option")
          ->each(function (Crawler $el) use (&$options) {
            $options[] = $el->attr('value');
          });
      }

      // populate values from user input
      if ($name == 'timezone') {
        $options = [];
        $this->browser->getCrawler()
          ->filter("select[name=timezone] > option")
          ->each(function (Crawler $el) use (&$options) {
            $label = $el->text();

            // move continent to end of string for better autocomplete
            $parts = explode("/", $label, 2);
            if (count($parts) == 2) {
              $label = $parts[1] . " (" . $parts[0] . ")";
            }

            $options[$el->attr('value')] = strtolower($label);
          });
        if (!$val) $val = array_search("utc", $options);

        // if timezone is set via option we overwrite it
        if ($t = $this->option('timezone')) {
          $default = $this->findTimezone($t, $options);
        } else $default = $options[$val];


        $label = "$name (type 'vienna' for Europe/Vienna to get autocomplete suggestions)";
        $label = $this->askWithCompletion($label, $options, $default);
        $value = array_search($label, $options);
        if ($this->output->isVerbose()) $this->write("$name=$value, $label");
      } elseif ($name == 'httpHosts') {
        $label = "$name (enter comma separated list)";
        if (!empty($default)) {
          $defaultLines = $this->cleanHttpHostsArray($default);
          $default = implode("\n", $defaultLines);
        }
        $value = $this->askWithCompletion($label, $options, $default);
        $hosts = is_string($value) ? preg_split('/[\n,]+/', $value) : (array)$value;
        $hosts = array_filter(array_map('trim', $hosts));
        $value = implode("\n", $hosts);
      } elseif ($name == 'admin_name') {
        $label = 'Enter url of your admin interface';
        $value = $this->ask($label, $this->option('url') ?: 'processwire');
        if ($this->output->isVerbose()) $this->write("$name=$value");
      } elseif ($name == 'userpass_confirm') {
        $value = $this->askWithCompletion($name, $options, $pass);
        if ($this->output->isVerbose()) $this->write("$name=$value");
      } elseif ($name == 'remove_items') {
        // THIS DOES NOT WORK YET :(
        continue;
        //   $this->browser->getCrawler()
        //     ->filter("input[type=checkbox][name^=$name]")
        //     ->each(function(Crawler $el, $i) use(&$form, $name) {
        //       $field = $form[$name."[$i]"];
        //       foreach($el->getNode(0)->parentNode->childNodes as $node) {
        //         $ask = $node->textContent;
        //         if(!$ask) continue;
        //         if($this->confirm($ask, true)) $field->untick();
        //         else $field->untick();
        //       }
        //     });
        //   // early exit
        //   continue;
      } elseif ($name == 'dbTablesAction') {
        if ($this->option('remove')) $value = 'remove';
        elseif ($this->option('ignore')) $value = 'ignore';
        else $value = $this->choice("DB not empty", [
          'remove',
          'ignore',
        ], 0);
        $this->warn("\ndbTablesAction: $value"); // show always
        $skipAll = true;      } else {
        $value = $this->askWithCompletion($name, $options, $default);
        if ($this->output->isVerbose()) $this->write("$name=$value");
      }

      // save password for later confirmation
      if ($name == 'userpass') $pass = $value;

      $form->setValues([$name => $value]);
    }

    return $form;
  }

  /**
   * @return string
   */
  public function findTimezone($t, $options)
  {
    if (is_string($t) and !(int)$t) {
      $t = strtolower($t);
      if (in_array($t, $options)) return $t;
      foreach ($options as $k => $v) {
        if (strpos($v, $t) !== false) return $v;
      }
    }
    return $options[$t];
  }

  /**
   * Get form values of current page
   * @return array
   */
  public function getFormValues()
  {
    $form = $this->browser->getCrawler()->filter('.InputfieldForm')->form();
    return $form->getValues();
  }

  public function getStarted()
  {
    $browser = $this->browser->submitForm('Get Started');
    $this->write($browser->getUri());
  }

  public function getStep()
  {
    $h1 = $this->browser->getCrawler()->filter('h1');
    $h1 = $h1->count() ? $h1->outerHtml() : '';
    if ($h1 !== '<h1 class="uk-margin-remove-top">ProcessWire 3.x Installer</h1>') {
      $this->write('No ProcessWire Installer found');
      if (is_file($this->app->docroot() . "index.php")) {
        $this->write("");
        $this->error("Found index.php - aborting ...");
        $this->write("");
        die();
      }
      if ($this->confirm("Download ProcessWire now?", true)) {
        $versions = ['master', 'dev'];
        $version = $this->option('dev')
          ? 'dev'
          : $this->choice("Which version?", $versions, "dev");

        $this->call("pw:download", ['version' => $version]);
        sleep(1);

        return $this->nextStep(true);
      }
      $this->warn("Aborting ...");
      die();
    }

    $headlines = [];
    foreach ($this->browser->getCrawler()->filter('h2') as $h2) {
      $headlines[] = $h2->textContent;
    }
    $headlines = array_reverse($headlines);
    foreach ($headlines as $headline) {
      $headline = trim($headline);
      if ($headline == 'Compatibility Check') return 'compatibility';
      if ($headline == 'Site Installation Profile') return 'profile';
      if (strpos($headline, "Welcome.") === 0) return 'welcome';
      if ($headline == 'Debug mode?') return 'database';
      if ($headline == 'Admin Panel') return 'admin';
      if ($headline == 'Admin Account Saved') return 'finish';
    }
    return $headlines;
  }

  public function host($site)
  {
    $defaulthost = getenv('DDEV_PROJECT') ? getenv('DDEV_PROJECT') . ".ddev.site" : "example.com";
    $site = ltrim($site, "/");
    $host = $this->host ?: $this->option('host') ?: $this->ask('Enter host', $defaulthost);
    $this->host = $host;

    // Ports from environment
    $httpPort = getenv('DDEV_ROUTER_HTTP_PORT');
    $httpsPort = getenv('DDEV_ROUTER_HTTPS_PORT');

    $urlsToCheck = [];
    if (parse_url($host, PHP_URL_PORT) === null) {  // No port in host
        if ($httpsPort) $urlsToCheck[] = $this->normalizeUrl("https://$host:$httpsPort");
        if ($httpPort) $urlsToCheck[] = $this->normalizeUrl("http://$host:$httpPort");
    } else {
        $urlsToCheck = [
          $this->normalizeUrl("https://$host"),
          $this->normalizeUrl("http://$host")
        ];
    }

    foreach ($urlsToCheck as $url) {
      $this->browser->request("GET", $url);
      $status = $this->browser->getInternalResponse()->getStatusCode();
      if ($status === 200) {
          $this->success("Status check for host $url was OK");
          return "$url/$site";
      }
      if ($status === 403) {
          $this->success("Status check for host $url was OK");
          $this->warn("Access is forbidden (403). This may be expected during installation.");
          return "$url/$site";
      }
    }

    $this->error("Your host $host must be reachable via HTTP or HTTPS!");
    $this->error("When using DDEV make sure it is running.");
    exit(1);
  }

  public function writeNotes()
  {
    $this->browser->getCrawler()
      ->filter("div.uk-section-muted > div.uk-container > div")
      ->each(function (Crawler $el) {
        $this->write("  " . $el->text());
      });
  }
}
