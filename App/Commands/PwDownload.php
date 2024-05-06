<?php

namespace RockShell;

use Symfony\Component\Console\Input\InputArgument;

class PwDownload extends Command
{

  public function config()
  {
    $this
      ->setDescription("Download ProcessWire")
      ->addArgument(
        "version",
        InputArgument::OPTIONAL,
        "ProcessWire version (master/dev)"
      );
  }

  public function handle()
  {
    $path = $this->app->docroot();
    chdir($path);
    $version = $this->argument('version')
      ?: $this->askWithCompletion(
        "Which version?",
        ['master', 'dev'],
        'dev'
      );
    $this->write("Downloading ProcessWire($version) to $path ...");
    $this->exec("wget --quiet https://github.com/processwire/processwire/archive/$version.zip");

    $this->write("Extracting files...");
    $this->exec("unzip -q $version.zip");

    // wait for unzip to be ready
    $cnt = 0;
    while (!is_dir("processwire-$version") and ++$cnt < 30) {
      $this->write("waiting for unzip...");
      sleep(1);
    }

    // cleanup
    // $this->exec("rm -rf processwire-$version");
    $this->write("Cleaning up temporary files...");
    $this->exec("rm $version.zip");
    $this->exec("mv processwire-$version pwtmp");
    $this->exec('find pwtmp -mindepth 1 -maxdepth 1 -exec mv -t ./ {} +');

    sleep(1);
    $this->exec("rm -rf pwtmp");

    return self::SUCCESS;
  }
}
