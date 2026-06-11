<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function currentUser(): ?array
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    protected function basePath(): string
    {
        return rtrim((string) ($_ENV['APP_BASE_PATH'] ?? ''), '/');
    }

    protected function redirect(string $path): void
    {
        $basePath = $this->basePath();
        header('Location: ' . $basePath . $path);
        exit;
    }
}
