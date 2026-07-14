<?php

namespace RockShell;

/**
 * Load $config->rockshell without booting ProcessWire.
 */
final class RockshellConfig
{
  public static function load(string $localConfigFile): array|false
  {
    if (!is_file($localConfigFile)) return false;
    $config = new \stdClass();
    include $localConfigFile;
    if (!isset($config->rockshell)) return false;
    return (array) $config->rockshell;
  }
}
