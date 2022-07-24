# RockShell

RockShell is a wrapper around the `symfony/console` component and inspired by Laravel's `Artisan Console`.

See https://symfony.com/doc/current/components/console.html and https://laravel.com/docs/8.x/artisan

## Kickstart a PW Project

```sh
git init
git submodule add git@github.com:baumrock/RockShell.git
cp RockShell/App/stubs/rs .
```

## How to use RockShell

Simply execute the rockshell file from your command line via your PHP interpreter:

```sh
php /path/to/your/project/RockShell/rs
```

If you want to use rockshell from your PW root directory you can copy it there (you only need to do that once for your project):

```sh
cd /path/to/your/project
cp RockShell/App/stubs/rs .
```

Then you can call rockshell from your PW root like this:

```sh
php rs
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
