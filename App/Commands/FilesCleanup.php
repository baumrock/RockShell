<?php

namespace RockShell;

use DirectoryIterator;
use Symfony\Component\Console\Input\InputOption;

class FilesCleanup extends Command
{

  public function config()
  {
    $this
      ->setDescription("Cleanup orphaned directories")
      ->addOption("delete", "d", InputOption::VALUE_NONE, "Delete without confirmation")
      ->addOption("show",   "s", InputOption::VALUE_NONE, "Show folders with matching page");
  }

  public function handle()
  {
    $path = $this->wire()->config->paths->files;
    $dir = new DirectoryIterator($path);

    $this->write($this->option("show"));

    $delete =
      $this->option("delete")
      ?: $this->confirm("Delete folders with no matching page? If you select <no>, folders will only be listed.");
    $showExisting =
      $this->option("show")
      ?: $this->confirm("Show folders with matching page?");

    foreach ($dir as $d) {
      if ($d->isDot()) continue;
      if (!$d->isDir()) continue;
      $id = $d->getFilename();
      $folder = $d->getPath() . "/$id";
      $files = $this->wire()->files->find($folder, [
        'returnRelative' => true,
      ]);
      $page = $this->wire()->pages->get($id);
      if ($page->id) {
        if (!$showExisting) continue;
        $this->success($folder);
        $this->write($files);
      } else {
        $this->error($folder);
        if (count($files)) {
          $this->write("  " . implode("\n  ", $files));
        }
        if ($delete) {
          $this->wire()->files->rmdir($folder, true);
          $this->write("-- deleted --");
        }
        $this->write("");
      }
    }

    return self::SUCCESS;
  }
}
