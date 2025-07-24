<?php

declare(strict_types=1);

/**
 * Parses, caches, and loads .env file variables
 */

namespace Env;

use Dotenv\Dotenv;
use ProcessWire\Config;
use RuntimeException;
use stdClass;

class Env
{
    private const LAST_MODIFIED_KEY = 'ENV_FILE_LAST_MODIFIED';

    private readonly string $cacheFile;

    /**
     * Memoized keys/values
     */
    private array $envData = [];

    private function __construct(
        private string $envLocation,
        private bool $createGlobalVars,
        private bool $importGlobalVars,
        private bool $castBools,
        private bool $castInts,
        private bool $exceptionOnMissing,
    ) {
        $this->cacheFile = __DIR__ . '/cache/env.php';

        $this->envData = self::loadEnv();
    }

    /**
     * Initialization method
     *
     * Load env config from cached file or .env if cache does not exist
     * @param bool $createGlobalVars      Create server env variables, otherwise values accessible by get()
     * @param bool $importGlobalVars   Import all variables that exist in the global $_ENV
     * @param bool $castBools          Cast 'true', 'false', '0', '1' values to booleans. If false,
     *                                 values will be strings
     * @param bool $castInts           Cast numbers to integers, if false values will be strings
     * @param bool $exceptionOnMissing Throw exception if key is missing, overrides fallback
     */
    public static function load(
        string $envLocation,
        bool $createGlobalVars = false,
        bool $importGlobalVars = false,
        bool $castBools = true,
        bool $castInts = true,
        bool $exceptionOnMissing = false,
    ): self {
        return new self(
            $envLocation,
            $createGlobalVars,
            $importGlobalVars,
            $castBools,
            $castInts,
            $exceptionOnMissing
        );
    }

    /**
     * Retreives values by key
     * @param string $key Environment variable name
     * @param mixed  $fallback
     * @throws RuntimeException
     */
    public function get(
        string $key,
        mixed $fallback = null,
        ?bool $exceptionOnMissing = null
    ): mixed {
        $exists = $this->exists($key);

        $exceptionOnMissing = $exceptionOnMissing ?? $this->exceptionOnMissing;

        if (!$exists && $exceptionOnMissing) {
            throw new RuntimeException(
                "The {$key} environment variable does not exist or could not be loaded"
            );
        }

        if ($exists) {
            return $this->envData[$key];
        }

        return $this->exists($fallback) ? $this->envData[$fallback] : $fallback;
    }

    /**
     * Check if an environment variable equals a value
     * @param  string  $key   Environment variable name
     * @param  mixed   $value Comparison value
     * @return boolean        match true/false
     */
    public function is(string $key, mixed $value): bool
    {
        return $this->get($key) === $value;
    }

    /**
     * Alias for is()
     *
     * @see Env::is()
     */
    public function eq(string $key, mixed $value): bool
    {
        return $this->is($key, $value);
    }

    /**
     * Check if an environment variable does not equal a value
     *
     * @param  string  $key   Environment variable name
     * @param  mixed   $value Comparison value
     * @return boolean        Not a match true/false
     */
    public function not(string $key, mixed $value): bool
    {
        return !$this->is($key, $value);
    }

    /**
     * Alias for not()
     *
     * @see Env::not()
     */
    public function notEq(string $key, mixed $value): bool
    {
        return $this->not($key, $value);
    }

    /**
     * An if statement that returns a value if the value for the env variable matches a value.
     * Optionally returns a false value if not
     *
     * @param  string     $key          Environment variable name
     * @param  mixed      $value        Expected environment variable value
     * @param  mixed      $valueIfTrue  Value returned if true, may be env variable name
     * @param  mixed|null $valueIfFalse Value returned if false, may be env variable name
     * @return mixed
     */
    public function if(
        string $key,
        mixed $value,
        mixed $valueIfTrue,
        mixed $valueIfFalse = null
    ): mixed {
        if (
            is_string($valueIfTrue) && $key === $valueIfTrue ||
            is_string($valueIfFalse) && $key === $valueIfFalse
        ) {
            throw new RuntimeException(
                "Env key and conditional value(s) must not match"
            );
        }

        if ($this->eq($key, $value)) {
            return $this->exists($valueIfTrue) ? $this->get($valueIfTrue) : $valueIfTrue;
        }

        return $this->exists($valueIfFalse) ? $this->get($valueIfFalse) : $valueIfFalse;
    }

    /**
     * Alias for if()
     *
     * @see Env::if()
     */
    public function ifEq(
        string $key,
        mixed $value,
        mixed $valueIfTrue,
        mixed $valueIsFalse = null
    ): mixed {
        return $this->if($key, $value, $valueIfTrue, $valueIsFalse);
    }

    /**
     * An if statement that returns a value if the value for the env variable does not match a value.
     * Optionally returns a false value if not
     *
     * @param  string     $key          Environment variable name
     * @param  mixed      $value        Expected environment variable value
     * @param  mixed      $valueIfTrue  Value returned if true
     * @param  mixed|null $valueIsFalse Value returned if false
     * @return mixed
     */
    public function ifNot(
        string $key,
        mixed $value,
        mixed $valueIfTrue,
        mixed $valueIfFalse = null
    ): mixed {
        if (
            is_string($valueIfTrue) && $key === $valueIfTrue ||
            is_string($valueIfFalse) && $key === $valueIfFalse
        ) {
            throw new RuntimeException(
                "Env key and conditional value(s) must not match"
            );
        }

        if (!$this->eq($key, $value)) {
            return $this->exists($valueIfTrue) ? $this->get($valueIfTrue) : $valueIfTrue;
        }

        return $this->exists($valueIfFalse) ? $this->get($valueIfFalse) : $valueIfFalse;
    }

    /**
     * Alias for ifNot()
     *
     * @see Env::ifNot()
     */
    public function ifNotEq(
        string $key,
        mixed $value,
        mixed $valueIfTrue,
        mixed $valueIsFalse = null
    ): mixed {
        return $this->ifNot($key, $value, $valueIfTrue, $valueIsFalse);
    }

    /**
     * Checks if a given environment variable exists
     *
     * @param  string $key Environment variable name
     * @return bool
     */
    public function exists(mixed $key): bool
    {
        if (!is_string($key)) {
            return false;
        }

        return array_key_exists($key, $this->envData);
    }

    /**
     * Gets all loaded environment variables/values as an array
     * @return array
     */
    public function getArray(): array
    {
        return $this->envData;
    }

    /**
     * Gets all loaded environment variables/values as an object
     * @return array
     */
    public function getObject(): stdClass
    {
        return (object) $this->getArray();
    }

    /**
     * Returns a ProcessWire config object that has all mapped property/env configurations loaded
     *
     * The $configMap array expects the following, whereas the key is the ProcessWire config
     * property and the value is the environment variable name associated with the desired value.
     *
     * A config map array value may be an array where index 0 is the environment variable name and
     * index 1 is a fallback value
     *
     * $configMap = [
     *     'dbHost' => 'DB_HOST',
     *     'dbName' => 'DB_NAME',
     *     'dbUser' => 'DB_USER',
     *     'dbPass' => 'DB_PASS',
     *     'debug' => ['DEBUG', false],
     *     'advanced' => ['ENABLE_ADVANCED', 'value'],
     * ];
     *
     * @param Config $processWirConfig The ProcessWire config object, mutates object
     * @param array  $configMap        The array of config to env var map
     */
    public function pushToConfig(Config &$processWireConfig, array $configMap): Config
    {
        foreach ($configMap as $configProperty => $value) {
            $fallback = null;

            if (is_array($value) && count($value) === 1) {
                $value = $value[0];
            }

            // A value may be an array of ['VAR_NAME', {fallback value or env var name}]
            // Check if first index is a variable name and treat it as a value/fallback if so
            if (is_array($value) && count($value) === 2) {
                $prop1 = $value[0];

                if (is_string($prop1) && $this->exists($prop1)) {
                    [$value, $fallback] = $value;
                }
            }

            // If this is an environment variable, assign value or fallback
            if (is_string($value) && $this->exists($value)) {
                $value = $this->get($value, $fallback);
            }

            $processWireConfig->{$configProperty} = $value;
        }

        return $processWireConfig;
    }

    /**
     * Deletes cached .env PHP file if it exists
     */
    public function clearCache(): void
    {
        if (!file_exists($this->cacheFile)) {
            return;
        }

        unlink($this->cacheFile);
    }

    /**
     * Loads data from cache, falls back to loading .env then caches
     */
    private function loadEnv(): ?array
    {
        $envVars = self::getCachedEnv();

        if (!$envVars || $this->envModified($envVars)) {
            $envVars = self::parseEnvFile();
            $envVars = $this->castValues($envVars);
            $envVars[self::LAST_MODIFIED_KEY] = $this->getEnvLastModified();

            $this->saveToCache($envVars);
        }

        $globalEnv = $_ENV;

        if ($this->createGlobalVars) {
            $this->pushToEnvironment($envVars);
        }

        if ($this->importGlobalVars) {
            $envVars = $globalEnv + $envVars;
        }

        return $envVars;
    }

    /**
     * Creates actual environment variables accessible by $_ENV[]
     */
    private function pushToEnvironment(array $envVars): void
    {
        foreach ($envVars as $key => $value) {
            $_ENV[$key] = $value;
        }
    }

    /**
     * Casts bool and int values retreived as strings to their correct type
     */
    private function castValues(array $envVars): array
    {
        if (!$this->castBools && !$this->castInts) {
            return $envVars;
        }

        // Convert booleans and integers if configured to
        array_walk($envVars, fn (&$value) => $value = $this->castValue($value));

        return $envVars;
    }

    /**
     * Casts values if casting is configured for this instance
     * @param  mixed  $value Env variable value
     * @return mixed
     */
    private function castValue(mixed $value): mixed
    {
        if (
            $this->castBools &&
            in_array($value, ['true', 'false'])
        ) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->castInts) {
            $ints = preg_replace('/[^\d]/', '', $value ?? '');

            // Check length of value with ints removed, if same length then convert value to int
            if (is_string($value) && strlen($ints) === strlen($value)) {
                return (int) $value;
            }
        }

        return $value;
    }

    /**
     * Load cached values by including the PHP as cache file
     */
    private function getCachedEnv(): ?array
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }

        $cachedEnv = require_once $this->cacheFile;

        if (!is_array($cachedEnv)) {
            return null;
        }

        if (
            !array_key_exists(self::LAST_MODIFIED_KEY, $cachedEnv) ||
            $cachedEnv[self::LAST_MODIFIED_KEY] !== $this->getEnvLastModified()
        ) {
            return null;
        }

        return $cachedEnv;
    }

    /**
     * New cache who dis
     */
    private function saveToCache(array $envVars): void
    {
        $envVars[self::LAST_MODIFIED_KEY] = $this->getEnvLastModified();

        file_put_contents(
            $this->cacheFile,
            '<?php return '. var_export($envVars, true) . ';' . PHP_EOL
        );
    }

    /**
     * Compares .env file last modified timestamp against cached .env vars
     * @param  array $lastModifiedTimestamp .env values to parse for last modified value
     * @return bool
     */
    private function envModified(array $cachedEnv): bool
    {
        $lastModifiedTimestamp = $cachedEnv[self::LAST_MODIFIED_KEY] ?? null;

        if (!$lastModifiedTimestamp) {
            return true;
        }

        return $this->getEnvLastModified() !== $lastModifiedTimestamp;
    }

    /**
     * Returns the last modified timestamp for the .env file
     * @return int Unix timestamp
     */
    private function getEnvLastModified(): int
    {
        return filemtime("{$this->envLocation}.env");
    }

    /**
     * Parse the .env file and return as associative array
     */
    private function parseEnvFile(): array
    {
        return Dotenv::createArrayBacked($this->envLocation)->load();
    }
}
