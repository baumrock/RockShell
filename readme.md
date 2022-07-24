# RockShell

RockShell is a wrapper around the `symfony/console` component and inspired by Laravel's `Artisan Console`.

See https://symfony.com/doc/current/components/console.html and https://laravel.com/docs/8.x/artisan

## How to use RockShell

Simply execute the rockshell file from your command line via your PHP interpreter:

```sh
php /path/to/your/project/RockShell/rockshell
```

If you want to use rockshell from your PW root directory you can copy it there (you only need to do that once for your project):

```sh
cd /path/to/your/project
cp RockShell/App/stubs/rshell .
```

Then you can call rockshell from your PW root like this:

```sh
php rockshell
```

## Creating custom commands

## Creating custom base commands

## Output

The output interface is available in your command's `output` property. See https://symfony.com/doc/current/console.html#console-output for details. RockShell provides some shortcuts that are easier to use:

```php
<?php namespace RockShell;
class Demo extends Command {
  public function handle() {
    # get symfony's output stream
    $this->output->writeln('output via symfony');

    # rockshell's specific shortcuts
    $this->info("I am an info message");
    $this->error("I am an error message");
    $this->comment("I am a comment");
    $this->question("I am a question");
    return self::SUCCESS;
  }
}
```

See all available options in `/rock/RockShell/Command.php` or read the symfony docs about coloring here: https://symfony.com/doc/current/console/coloring.html

## Input

Similar to the output section the input interface is available from the `input` property of your command. See https://symfony.com/doc/current/console.html#console-input for details. Again RockShell provides some handy helpers that make it even easier to deal with user input:

```php
<?php namespace Commands;
class Demo extends Command {
  public function handle() {
    $this->info("I am an info message");
    $this->error("I am an error message");
    $this->comment("I am a comment");
    $this->question("I am a question");
    return self::SUCCESS;
  }
}
```
