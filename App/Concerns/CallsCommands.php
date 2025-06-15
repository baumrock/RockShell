<?php

namespace RockShell\Concerns;

use Symfony\Component\Console\Input\ArrayInput;

/**
 * Trait for calling other commands
 */
trait CallsCommands
{
    /**
     * Call another command
     */
    protected function call(string $command, array $arguments = [])
    {
        $application = $this->getApplication();
        $command = $application->find($command);
        
        $input = new ArrayInput(array_merge(['command' => $command->getName()], $arguments));
        return $command->run($input, $this->output);
    }
}