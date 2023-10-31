# Configuration

Some commands need an SSH connection to a remote server and also need to know the path of the remote ProcessWire installation so that RockShell can be executed there.

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