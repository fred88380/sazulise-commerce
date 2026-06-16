<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const SESSION_KEY = '__csrf_token';

    public static function token(): string
    {
        $token = (string) ($_SESSION[self::SESSION_KEY] ?? '');
        if ($token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        return $token;
    }

    public static function field(): string
    {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="__csrf" value="' . $token . '">';
    }

    public static function isValid(?string $candidate): bool
    {
        if (!is_string($candidate) || $candidate === '') {
            return false;
        }

        $token = (string) ($_SESSION[self::SESSION_KEY] ?? '');
        if ($token === '') {
            return false;
        }

        return hash_equals($token, $candidate);
    }

    public static function validateRequest(): bool
    {
        $candidate = (string) ($_POST['__csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        return self::isValid($candidate);
    }
}
