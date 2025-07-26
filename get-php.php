<?php

// try to get config from ../.rockshell-config.json
$file = __DIR__ . '/../.rockshell.json';
if (file_exists($file)) {
  $config = json_decode(file_get_contents($file), true);
  echo $config['php'] ?? 'php';
} else {
  echo 'php';
}
