
/**
 * Load local config
 * Added via rockshell pw:install command.
 * This makes it possible to add config.php to your git project.
 * Move all secrets to config-local.php and make sure it is ignored by git.
 */
$localConfig = __DIR__."/config-local.php";
if(is_file($localConfig)) include $localConfig;
