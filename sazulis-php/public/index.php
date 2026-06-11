<?php

declare(strict_types=1);

use App\Core\Router;

require_once dirname(__DIR__) . '/bootstrap.php';

$router = new Router();

$registerWeb = require dirname(__DIR__) . '/routes/web.php';
$registerApi = require dirname(__DIR__) . '/routes/api.php';

$registerWeb($router);
$registerApi($router);

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$base = rtrim($_ENV['APP_BASE_PATH'] ?? '/sazulis/sazulis-php', '/');
$basePublic = $base . '/public';

if (str_starts_with($uri, $basePublic)) {
    $uri = substr($uri, strlen($basePublic)) ?: '/';
} elseif (str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base)) ?: '/';
}

$uriPath = parse_url($uri, PHP_URL_PATH) ?: '/';
if ($uriPath === '/index.php' || $uriPath === '/public/index.php') {
    $uri = '/';
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri);
