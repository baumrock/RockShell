# Installation

<div class="uk-alert uk-alert-danger">Caution: Do not install RockShell into /site/modules like other modules!</div>

RockShell needs to be saved in the folder `/path/to/pwroot/RockShell` so that the `rock` executable lives in `/path/to/pwroot/RockShell/rock`.

You can either git clone RockShell there:

```sh
cd /path/to/pwroot
git clone https://github.com/baumrock/RockShell
```

Or you can just download all files and copy them manually.

## First run

Now you can already run RockShell!

```sh
php /path/to/pwroot/RockShell/rock

# or like this
cd /path/to/pwroot
php RockShell/rock
```

Pro-Tipp: Create an alias for that command so that you can simply use `rockshell` to interact with your PW instance! See the example below how that alias looks like on DDEV

<img src=rockshell.png class=blur>

## Using DDEV

When using DDEV for local development you need to execute RockShell from within your container:

```sh
ddev exec php RockShell/rock ...
```

I'm lazy and I use RockShell all the time so I created this alias:

```sh
function rockshell() {
  ddev exec php RockShell/rock "$@"
}
```

Now I can simply type `rockshell ...` or commands like `rockshell db:pull staging` directly on my host machine and it will execute RockShell within the container 😎🚀

<img src=alias.png class=blur>
