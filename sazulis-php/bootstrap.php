<?php

declare(strict_types=1);

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

if ($https) {
    ini_set('session.cookie_secure', '1');
}

session_name('sazulis_session');
session_start();

if (!isset($_SESSION['__session_started_at'])) {
    $_SESSION['__session_started_at'] = time();
    session_regenerate_id(true);
}

if (!headers_sent()) {
    ini_set('expose_php', '0');
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
    header("Content-Security-Policy: default-src 'self'; base-uri 'self'; frame-ancestors 'self'; form-action 'self'; object-src 'none'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self';");
    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

set_exception_handler(static function (\Throwable $e): void {
    error_log('[SAZULIS][EXCEPTION] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    $errorPage = __DIR__ . '/public/errors/500.php';
    if (is_file($errorPage)) {
        require $errorPage;
        return;
    }
    echo '500 - Internal server error';
});

register_shutdown_function(static function (): void {
    $lastError = error_get_last();
    if ($lastError === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int) ($lastError['type'] ?? 0), $fatalTypes, true)) {
        return;
    }

    error_log('[SAZULIS][FATAL] ' . (string) ($lastError['message'] ?? 'Unknown fatal error'));
    if (!headers_sent()) {
        http_response_code(500);
    }

    $errorPage = __DIR__ . '/public/errors/500.php';
    if (is_file($errorPage)) {
        require $errorPage;
        return;
    }

    echo '500 - Internal server error';
});

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
