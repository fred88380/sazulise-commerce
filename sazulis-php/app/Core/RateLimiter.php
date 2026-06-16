<?php

declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    private const SESSION_KEY = '__rate_limiter';

    public static function tooManyAttempts(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        self::ensure($key, $windowSeconds);
        $bucket = &$_SESSION[self::SESSION_KEY][$key];

        return (int) ($bucket['count'] ?? 0) >= $maxAttempts;
    }

    public static function hit(string $key, int $windowSeconds): void
    {
        self::ensure($key, $windowSeconds);
        $_SESSION[self::SESSION_KEY][$key]['count'] = (int) ($_SESSION[self::SESSION_KEY][$key]['count'] ?? 0) + 1;
    }

    public static function clear(string $key): void
    {
        unset($_SESSION[self::SESSION_KEY][$key]);
    }

    private static function ensure(string $key, int $windowSeconds): void
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_array($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }

        $now = time();
        $bucket = $_SESSION[self::SESSION_KEY][$key] ?? null;

        if (!is_array($bucket) || $now >= (int) ($bucket['reset_at'] ?? 0)) {
            $_SESSION[self::SESSION_KEY][$key] = [
                'count' => 0,
                'reset_at' => $now + max(1, $windowSeconds),
            ];
        }
    }
}
