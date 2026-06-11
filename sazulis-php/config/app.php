<?php

declare(strict_types=1);

return [
    'app_name' => $_ENV['APP_NAME'] ?? 'Sazulis Commerce',
    'base_url' => $_ENV['APP_URL'] ?? 'http://localhost/sazulis/sazulis-php',
    'base_path' => $_ENV['APP_BASE_PATH'] ?? '',
    'db' => [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['DB_PORT'] ?? '3306',
        'database' => $_ENV['DB_NAME'] ?? 'sazulis_v2',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'charset' => 'utf8mb4',
    ],
];
