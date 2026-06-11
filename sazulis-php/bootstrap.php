<?php

declare(strict_types=1);

session_start();

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

$envPath = __DIR__ . '/.env';
if (is_file($envPath)) {
    $pairs = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($pairs as $pair) {
        if (str_starts_with(trim($pair), '#') || !str_contains($pair, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $pair, 2));
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

if (empty($_ENV['APP_BASE_PATH'])) {
    $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = str_replace('\\', '/', dirname($scriptName));
    $basePath = $basePath === '.' ? '' : rtrim($basePath, '/');
    $_ENV['APP_BASE_PATH'] = $basePath;
    putenv('APP_BASE_PATH=' . $basePath);
}

$config = require __DIR__ . '/config/app.php';
