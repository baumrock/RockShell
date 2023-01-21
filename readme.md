# RockShell

RockShell is a wrapper around the `symfony/console` component and inspired by Laravel's `Artisan Console`.

See https://symfony.com/doc/current/components/console.html and https://laravel.com/docs/8.x/artisan

## Kickstart a PW Project

```sh
cd /path/to/your/project
git init
git submodule add git@github.com:baumrock/RockShell.git
cd RockShell
php rockshell pw-install
```

## How to use RockShell

Simply execute the rockshell file from your command line via your PHP interpreter:

```sh
php /path/to/your/project/RockShell/rockshell

# or
cd /path/to/your/project/RockShell
php rockshell
```

### Setting up remotes

```php
$config->rockshell = [
  // optional: use different php version
  // 'remotePHP' => 'keyhelp-php81',
  'remotes' => [
    'staging' => [
      'ssh' => 'user@host.com',
      'dir' => '/path/to/your/site',
    ],
    'production' => [
      'ssh' => 'user@host.com',
      'dir' => '/path/to/your/site',
    ],
  ],
];
```

## Creating custom commands

## Creating custom base commands

## Output

The output interface is available in your command's `output` property. See https://symfony.com/doc/current/console.html#console-output for details. RockShell provides some shortcuts that are easier to use:

```php
<?php namespace RockShell;
class HelloWorld extends Command {
  public function handle() {
    $this->info("Hello World!");
    return self::SUCCESS;
  }
}
```

See the symfony docs about coloring here: https://symfony.com/doc/current/console/coloring.html
