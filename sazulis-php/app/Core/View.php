<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $viewPath = __DIR__ . '/../Views/' . $view . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            echo 'View not found: ' . htmlspecialchars($view, ENT_QUOTES, 'UTF-8');
            return;
        }

        $data['csrfToken'] = $data['csrfToken'] ?? Csrf::token();
        $data['csrfField'] = $data['csrfField'] ?? Csrf::field();

        extract($data, EXTR_SKIP);
        $contentView = $viewPath;
        require __DIR__ . '/../Views/layouts/main.php';
    }
}