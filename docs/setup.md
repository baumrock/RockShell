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

You can either call the `rock` file directly as shown above or you can create a symlink that points to that file, so that you can call `php rock` directly from within the PW root folder:

```sh
cd /path/to/pw/RockShell
php rock symlink

# now that the symlink exists you can use short calls:
cd /path/to/pw
php rock ...
```

<img src=rockshell.png class=blur>

## Using DDEV

When using DDEV for local development you need to execute RockShell from within your container:

```sh
ddev exec php rock ...
```

I'm lazy and I use RockShell all the time so I created this alias:

```sh
function rockshell() {
  ddev exec php rock "$@"
}
```

Now I can simply type `rockshell ...` directly on my host machine and it will execute RockShell within the container 😎🚀
