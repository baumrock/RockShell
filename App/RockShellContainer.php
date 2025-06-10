<?php

namespace RockShell;

use Illuminate\Container\Container;

/**
 * Custom Container for RockShell
 * 
 * Laravel 11 added interactive prompts that need to be disabled during unit tests.
 * The Console Command calls runningUnitTests() to detect test environment, but
 * standalone Container doesn't have this method. This provides it.
 * 
 * @see https://github.com/laravel/framework/blob/11.x/src/Illuminate/Console/Concerns/ConfiguresPrompts.php#L36
 */
class RockShellContainer extends Container
{
    /**
     * Determine if the application is running unit tests.
     * 
     * @return bool
     */
    public function runningUnitTests(): bool
    {
        return false; // RockShell is not running unit tests
    }
}