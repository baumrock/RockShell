<?php

namespace RockShell\Concerns;

/**
 * Trait for commands that require ProcessWire to be available
 */
trait RequiresProcessWire
{
    /**
     * Get ProcessWire instance or fail gracefully
     */
    protected function requireProcessWire()
    {
        $wire = $this->wire();
        if (!$wire) {
            $this->error("ProcessWire not found or not installed.");
            $this->write("Please run this command from an installed ProcessWire directory.");
            $this->write("Project Root: " . $this->app->rootPath());
            $this->write("ProcessWire Root: " . $this->app->wireRoot());
            exit(1); // Failure exit code
        }
        return $wire;
    }
}
