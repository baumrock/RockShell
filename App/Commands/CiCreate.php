<?php

namespace RockShell;

/**
 * Create a CI workflow for a project
 *
 * Usage:
 * rockshell ci:create
 */
class CiCreate extends Command
{

  public function config()
  {
    $this->setDescription("Create a CI workflow for a project");
  }

  public function handle()
  {
    $this->write($this->app->rootPath());

    $environment = $this->ask("Environment name", "STAGING");
    $branchname = $this->ask("Branch name", "dev");
    $this->stubPopulate(
      src: "workflow.txt",
      dst: $this->app->rootPath() . ".github/workflows/$environment.yaml",
      vars: [
        "environment" => $environment,
        "branchname" => $branchname,
      ],
    );
    return self::SUCCESS;
  }
}
