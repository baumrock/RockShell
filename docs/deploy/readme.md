# Deployments

## How it works

The RockShell deployment system uses a zero-downtime strategy with atomic releases. Here's how the complete workflow operates:

### Server-Side Architecture

The deployment creates a structured directory layout on the server:

```
/var/www/your-site/
├── current/          # Symlink to active release
├── shared/           # Persistent data across releases
│   ├── site/assets/files/
│   ├── site/assets/sessions/
│   └── site/assets/logs/
├── release-2025-01-14--10-15-30--def67890/      # Previous release
├── release-2025-01-15--14-30-00--abc12345/      # Current release
└── tmp-release-2025-01-20--16-00-00--xyz12345/  # New release being deployed
```

### GitHub Actions Workflow

The deployment is triggered by Git pushes and runs through these main jobs:

- **setup**: Validates environment variables and prepares deployment configuration.
- **write-config**: Generates an environment-specific configuration file `conf.php`.
- **deploy**: Transfers files to the server using `rsync` and then runs the PHP deployment script.

## Improvements to RockMigrations Deployments

### No need for rm:transform and manual setup

RockMigrations deployments used a similar strategy but the initial deployment had to be done manually. You had to create a `config-local.php` file and you had to run the `rm:transform` command to create the symlink/shared directory structure. This is all handled automatically by RockShell Deployments!

### Fails Early for Fast Debugging

The deployment workflow checks all required variables and secrets at the start. If anything is missing or SSH cedentials do not work, it fails immediately with a clear error, so you can fix issues quickly without waiting for a long running job to finish.

<img src=https://i.imgur.com/XAHrliO.png class=blur>

After adding the missing variables or secrets, simply re-run the workflow. There’s no need to make another Git push!

### Use Without RockMigrations

You can deploy any ProcessWire site with this workflow without installing RockMigrations. If present, migrations will run automatically; if not, deployment works as usual.

### Deployment Flag

When a deployment is in progress, a special "deployment flag" file is created on the server. This file is used to indicate that a deployment is currently running, and can be checked by your application or by administrators to prevent conflicting operations or to display maintenance messages.

## deploy.whenDone.php

The `deploy.whenDone.php` file is a special script that runs automatically after a successful deployment. It's executed in the final stage of the deployment process, after all files have been transferred, migrations have run, and the symlink has been updated to point to the new release.

### When it runs

The script is executed by the `runWhenDone()` method in the `Deployment` class, which:

1. Runs after the symlink has been updated to point to the new release
2. Executes before the cleanup of old releases
3. Only runs if the file exists in the new release directory
4. Uses the same PHP interpreter as the rest of the deployment process

### Purpose and Use Cases

The `deploy.whenDone.php` file is perfect for:

- **Cache clearing**: Clear application caches that might contain old data
- **Service restarts**: Restart background services or workers
- **Database cleanup**: Run post-deployment database maintenance
- **File permissions**: Set correct permissions on uploaded files
- **Health checks**: Verify the deployment was successful
- **Notifications**: Send deployment notifications to team members

### Example Implementation

Here's the current implementation that clears a specific cache:

```php
<?php

namespace ProcessWire;

use function ProcessWire\wire;

require_once __DIR__ . '/public/index.php';

wire()->cache->delete('reactpdf-running');
echo "reactpdf-running cache deleted\n";
```
