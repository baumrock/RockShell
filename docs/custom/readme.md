# Custom Commands

Creating commands is as easy as adding one simple file to your project. You can place commands in `/site/assets/RockShell/Commands` or any of your modules `/site/modules/*/RockShell/Commands`. This command will then be available to your rockshell interface:

<img src=https://i.imgur.com/pRc8B9t.gif class=blur>

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
