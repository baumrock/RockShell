<?php

namespace RockShell\Concerns;

/**
 * Trait for accessing command parameters (options and arguments)
 */
trait HasParameters
{
    /**
     * Get command option value
     */
    protected function option(string $name)
    {
        return $this->input->getOption($name);
    }

    /**
     * Get command argument value
     */
    protected function argument(string $name)
    {
        return $this->input->getArgument($name);
    }
}