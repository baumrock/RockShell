<?php

namespace RockShell;

/**
 * Create a CI workflow for a project
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

    $envName = $this->ask("Environment name", "STAGING");
    $workflowName = $this->ask("Workflow name", "Deploy to $envName");
    $branchName = $this->ask("Branch name", "dev");
    $useVersion = $this->ask("Deployment Version", "dev");

    $this->stubPopulate(
      src: "workflow.txt",
      dst: $this->app->rootPath() . ".github/workflows/$envName.yaml",
      vars: [
        "workflowname" => $workflowName,
        "branchname" => $branchName,
        "useversion" => $useVersion,
        "environment" => $envName,
      ],
    );
    return self::SUCCESS;
  }
}
