# Custom Commands

Creating commands is as easy as adding one simple file to your project. You can place commands in `/site/assets/RockShell/Commands` or any of your modules `/site/modules/*/RockShell/Commands`.

<div class="uk-alert uk-alert-warning">Note that if you place it in one of your modules you need to adjust the namespace of your command to match your modules folder name.</div>

Here is an example hello world command placed in `/site/assets/RockShell/Commands`:

```php
<?php

// when placed in the assets folder
// we need to use the Assets namespace!
namespace Assets;

use RockShell\Command;

class HelloWorld extends Command
{
  public function handle()
  {
    $this->info("Hello World!");
    return self::SUCCESS;
  }
}
```

Another example command can be found in RockMigrations: https://github.com/baumrock/RockMigrations/blob/main/RockShell/Commands/RmDemo.php

The command uses the `RockMigrations` namespace to make sure we don't get any naming collisions.

<img src=cmd.png class=blur>

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
