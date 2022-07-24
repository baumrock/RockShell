<?php namespace RockShell;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\HttpClient\HttpClient;

class Install extends Command {

  /** @var HttpBrowser */
  private $browser;

  private $host;

  private $skipWelcome = false;
  private $skipNextConfirm = false;

  private $stepCount = 0;

  public function config() {
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
      ->addOption("dev", null, InputOption::VALUE_NONE, "Download dev version of pw?")
    ;
  }

  public function handle() {
    if($this->ddevExists() AND !$this->ddev()) {
      $this->error("Use ddev ssh to execute this command from within DDEV");
      return self::FAILURE;
    }
    $this->browser = new HttpBrowser(HttpClient::create());
    $this->nextStep(true);
    return self::SUCCESS;
  }

  /** ##### steps ##### */

  public function nextStep($reload = false) {
    $this->stepCount = $this->stepCount+1;
    if($this->stepCount > 30) return; // hard limit to prevent endless loops

    // if a reload is required we fire another request
    if($reload) $this->browser->request('GET', $this->host('install.php'));

    // get current step from headline
    $step = $this->getStep();
    if(!$step) return false;
    if(is_array($step)) return $this->warn($this->str($step));

    // show errors
    $this->browser->getCrawler()->filter("div.uk-alert")
      ->each(function(Crawler $el) {
        $this->warn($el->text());
      });

    // execute the step
    $method = "step".ucfirst($step);
    $next = $this->$method();
    if($this->option('debug')) {
      $this->warn("Step $step (debug mode) - entering interactive shell");
      $this->pause([
        'browser' => $this->browser,
      ]);
    }

    // next step?
    // if the last step returned false we break
    if($next !== false) {
      if($this->skipNextConfirm) return $this->nextStep();
      if($this->confirm("Continue to next step?", true)) return $this->nextStep();
    }
  }

  public function stepWelcome() {
    if(!$this->skipWelcome) $this->write('Welcome');
    $this->browser->submitForm('Get Started');
  }

  public function stepProfile() {
    $this->write("Installing profile...");

    $profiles = $this->browser
      ->getCrawler()
      ->filter('select[name=profile] > option')
      ->extract(['value']);
    if(!count($profiles)) {
      $this->error("No profiles found - aborting...");
      die();
    }
    $profiles = array_values(array_filter($profiles));
    if(!$profile = $this->option('profile')) {
      $profile = $this->askWithCompletion("Select profile to install",
        $profiles, $profiles[0]);
    }
    $this->write("Using profile $profile...");
    $this->browser->submitForm("Continue", [
      'profile' => $profile,
    ]);
  }

  public function stepCompatibility() {
    $this->write("Checking compatibility...");
    $errors = 0;
    $this->browser
      ->getCrawler()
      ->filter('div.uk-section-muted > div.uk-container > div')
      ->each(function(Crawler $el) use(&$errors) {
        $text = $el->text();
        $outer = $el->outerHtml();
        if(strpos($outer, 'fa-check')) $this->success($text);
        else {
          $errors++;
          $this->warn($text);
        }
      });
    if($errors) {
      if($this->confirm("Check again?", true)) {
        $this->skipWelcome = true;
        $this->skipNextConfirm = true;
        return $this->nextStep(true);
      }
      else {
        if($this->confirm("Continue Installation?", false)) {
          $this->skipNextConfirm = true;
          $this->browser->submitForm('Continue to Next Step');
        }
        else {
          $this->warn('Aborting...');
          die();
        }
      }
    }
    else {
      $this->skipNextConfirm = false;
      $this->browser->submitForm('Continue to Next Step');
    }
  }

  public function stepDatabase() {
    $this->write("Setting the following sections:");
    $this->browser->getCrawler()->filter('h2')->each(function(Crawler $el) {
      $this->write("  ".$el->text());
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
  }

  public function stepAdmin() {
    $this->write("Setup admin panel and user");
    $form = $this->fillForm([
      'admin_name' => $this->option('url') ?: 'processwire',
      'username' => $this->option('name') ?: 'ddevadmin',
      'userpass' => $this->option('pass') ?: 'ddevadmin',
      'useremail' => $this->option('mail'),
    ]);
    if($this->output->isVeryVerbose()) var_dump($form->getValues());
    $this->browser->submitForm("Continue", $form->getValues());
  }

  public function stepFinish() {
    $this->success("Finishing installation...");
    $this->writeNotes();
    return $this->stepReloadAdmin();
  }

  public function stepReloadAdmin($notice = true) {
    if($notice) $this->info("\nLoading ProcessWire ...");
    chdir($this->app->rootPath());
    include "index.php";
    /** @var ProcessWire $wire */
    $url = $this->host($wire->pages->get(2)->url);
    if($notice) $this->write($url);
    $this->browser->request('GET', $url);

    $notices = 0;
    $this->browser->getCrawler()
      ->filter("li.NoticeMessage")
      ->each(function(Crawler $el) use(&$notices) {
        $notices++;
        $this->write($el->text());
      });
    if($notices) {
      $this->warn("Reloading...");
      return $this->stepReloadAdmin(false);
    }
    else {
      $this->success("\n"
        ."##### INSTALL SUCCESSFUL ######\n"
        ."### powered by baumrock.com ###");
      $this->warn("Login: $url");
      $this->warn("\n##### You can now call pw:setup #####\n");
      die();
    }
  }

  /** ##### end steps ##### */

  /**
   * @return Form
   */
  public function fillForm($defaults = []) {
    $form = $this->browser->getCrawler()->filter('.InputfieldForm');
    if(!$form->count()) return $this->error('No form found');
    $form = $form->form();
    $values = $form->getPhpValues();
    $pass = '';

    $skipAll = false;
    foreach($values as $name=>$val) {
      if($skipAll) continue;
      // if the value is set via option we take this value
      try {
        $val = $this->option($name);
      } catch (\Throwable $th) {}

      // otherwise we continue and ask the user for input
      $options = [];
      $field = $form[$name];
      $default = $val;
      if(array_key_exists($name, $defaults)) $default = $defaults[$name];

      if($this->output->isVeryVerbose()) $this->warn($name);
      if($field instanceof ChoiceFormField) {
        $this->browser
          ->getCrawler()
          ->filter("input[name=$name],select[name=$name] > option")
          ->each(function(Crawler $el) use(&$options) {
            $options[] = $el->attr('value');
          });
      }

      // populate values from user input
      if($name == 'timezone') {
        $options = [];
        $this->browser->getCrawler()
          ->filter("select[name=timezone] > option")
          ->each(function(Crawler $el) use(&$options) {
            $label = $el->text();

            // move continent to end of string for better autocomplete
            $parts = explode("/", $label, 2);
            if(count($parts)==2) {
              $label = $parts[1]." (".$parts[0].")";
            }

            $options[$el->attr('value')] = strtolower($label);
          });
        if(!$val) $val = array_search("utc", $options);

        // if timezone is set via option we overwrite it
        if($t = $this->option('timezone')) {
          $default = $this->findTimezone($t, $options);
        }
        else $default = $options[$val];

        $label = $this->askWithCompletion($name, $options, $default);
        $value = array_search($label, $options);
        if($this->output->isVerbose()) $this->write("$name=$value, $label");
      }
      elseif($name == 'httpHosts') {
        $label = "$name (enter comma separated list)";
        $value = $this->askWithCompletion($label, $options, $default);
        $hosts = explode(",", $value);
        $hosts = array_filter(array_map('trim', $hosts));
        if($this->output->isVerbose()) $this->write("$name=".implode(",", $hosts));
        $value = implode("\n", $hosts);
      }
      elseif($name == 'userpass_confirm') {
        $value = $this->askWithCompletion($name, $options, $pass);
        if($this->output->isVerbose()) $this->write("$name=$value");
      }
      elseif($name == 'remove_items') {
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
      }
      elseif($name == 'dbTablesAction') {
        if($this->option('remove')) $value = 'remove';
        elseif($this->option('ignore')) $value = 'ignore';
        else $value = $this->choice("DB not empty", [
          'remove',
          'ignore',
        ]);
        $this->warn("\ndbTablesAction: $value"); // show always
        $skipAll = true;
      }
      else {
        $value = $this->askWithCompletion($name, $options, $default);
        if($this->output->isVerbose()) $this->write("$name=$value");
      }

      // save password for later confirmation
      if($name == 'userpass') $pass = $value;

      $form->setValues([$name => $value]);
    }

    return $form;
  }

  /**
   * @return string
   */
  public function findTimezone($t, $options) {
    if(is_string($t) AND !(int)$t) {
      $t = strtolower($t);
      if(in_array($t, $options)) return $t;
      foreach($options as $k=>$v) {
        if(strpos($v, $t)!==false) return $v;
      }
    }
    return $options[$t];
  }

  /**
   * Get form values of current page
   * @return array
   */
  public function getFormValues() {
    $form = $this->browser->getCrawler()->filter('.InputfieldForm')->form();
    return $form->getValues();
  }

  public function getStarted() {
    $browser = $this->browser->submitForm('Get Started');
    $this->write($browser->getUri());
  }

  public function getStep() {
    $h1 = $this->browser->getCrawler()->filter('h1');
    $h1 = $h1->count() ? $h1->outerHtml() : '';
    if($h1 !== '<h1 class="uk-margin-remove-top">ProcessWire 3.x Installer</h1>') {
      $this->warn('ProcessWire Installer not found');
      if(is_file($this->app->rootPath()."index.php")) {
        $this->write("");
        $this->error("Found index.php - aborting...");
        $this->write("");
        die();
      }
      if($this->confirm("Download ProcessWire now?", true)) {
        $versions = ['master', 'dev'];
        $version = $this->option('dev')
          ? 'dev'
          : $this->choice("Which version?", $versions, "dev");
        $this->call("download", [
          'version' => $version,
        ]);
        sleep(1);
        return $this->nextStep(true);
      }
      $this->warn("Aborting...");
      die();
    }

    $headlines = [];
    foreach($this->browser->getCrawler()->filter('h2') as $h2) {
      $headlines[] = $h2->textContent;
    }
    $headlines = array_reverse($headlines);
    foreach($headlines as $headline) {
      $headline = trim($headline);
      if($headline == 'Compatibility Check') return 'compatibility';
      if($headline == 'Site Installation Profile') return 'profile';
      if(strpos($headline, "Welcome.") === 0) return 'welcome';
      if($headline == 'Debug mode?') return 'database';
      if($headline == 'Admin Panel') return 'admin';
      if($headline == 'Admin Account Saved') return 'finish';
    }
    return $headlines;
  }

  public function host($site) {
    $site = ltrim($site, "/");
    $checkHTTP = !$this->host;
    $host = $this->host ?: $this->option('host')
      ?: $this->ask('Enter host, eg example.ddev.site');
    $this->host = $host;

    // check if host is reachable via HTTP
    if($checkHTTP) {
      $this->browser->request("GET", $host);
      $status = $this->browser->getInternalResponse()->getStatusCode();
      if($status !== 404) {
        $this->error("Your host $host must be reachable via HTTP!");
        $this->error("When using DDEV make sure it is running ;)");
        exit(1);
      }
    }

    return "https://$host/$site";
  }

  public function writeNotes() {
    $this->browser->getCrawler()
      ->filter("div.uk-section-muted > div.uk-container > div")
      ->each(function(Crawler $el) {
        $this->write("  ".$el->text());
      });
  }

}
