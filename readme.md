# RockShell: The Command-Line Companion for ProcessWire

RockShell is a powerful command-line tool that makes it easy to install, configure, and manage ProcessWire websites. 

With RockShell, you can:

* Install ProcessWire with a single command
* Create a backup of your database with a single command
* Restore your database from a backup with a single command
* Define customs commands

It is based on the [symfony/console]( https://symfony.com/doc/current/components/console.html) component and inspired by Laravel's [Artisan Console](https://laravel.com/docs/10.x/artisan).

<img width="719" alt="image" src="https://github.com/baumrock/RockShell/assets/8488586/3858509e-5522-476c-acd0-dd31545a7c4f">

## Installation / Setup

Clone this repo into `/path/to/yourproject/`

with `git clone https://github.com/baumrock/RockShell`

Or you could just download all files and copy them into /path/to/yourproject/RockShell

Do NOT install the module via the PW Backend!

## How to use RockShell

Simply execute the rockshell file from your command line via your PHP interpreter:

```sh
php /path/to/your/project/RockShell/rock
```
or
```sh
cd /path/to/your/project/RockShell
php rock
```

You can either call the `rock` file directly as shown above or you can create a symlink that points to that file, so that you can call `php rock` directly from within the PW root folder:

```sh
cd /path/to/pw/RockShell
php rock symlink

# now that the symlink exists you can use short calls:
cd /path/to/pw
php rock ...
```

### Setting up remotes

```php
$config->rockshell = [
  // optional: use different php version
  // 'remotePHP' => 'keyhelp-php81',
  'remotes' => [
    'staging' => [
      'ssh' => 'user@host.com',
      'dir' => '/path/to/your/site/current',
    ],
    'production' => [
      'ssh' => 'user@host.com',
      'dir' => '/path/to/your/site/current',
    ],
  ],
];
```

## Creating custom commands

Creating commands is as easy as adding one single file with a PHP class with two methods to your project. You can place commands in `/site/assets/RockShell/Commands` or in `/site/modules/*/RockShell/Commands`. This command will be then available to your rockshell interface:

<img src=https://i.imgur.com/pRc8B9t.gif>

## Creating custom base commands

TBD

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

Check out the `php rock ping` command and see the symfony docs about coloring here: https://symfony.com/doc/current/console/coloring.html
