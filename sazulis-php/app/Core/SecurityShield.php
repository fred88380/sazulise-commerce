<?php

declare(strict_types=1);

namespace App\Core;

final class SecurityShield
{
    private const STORAGE_DIR = '/storage/security';
    private const THRESHOLD_BAN = 10;

    public static function enforce(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $ip = self::clientIp();
        $state = self::loadState($ip);
        $now = time();

        if (($state['ban_until'] ?? 0) > $now) {
            self::deny('Acces temporairement bloque.');
        }

        self::applyRateLimit($state, $now);

        $payload = self::collectPayload();
        $signals = self::detectSignals($payload);

        foreach ($signals as $signal) {
            self::applySignal($state, $signal, $now);
        }

        if (($state['strikes'] ?? 0) >= self::THRESHOLD_BAN) {
            $state['ban_until'] = max((int) ($state['ban_until'] ?? 0), $now + (30 * 24 * 3600));
            $state['last_reason'] = 'securite cumulative';
        }

        self::saveState($ip, $state);

        if (($state['ban_until'] ?? 0) > $now) {
            self::deny('Acces bloque pour raisons de securite.');
        }
    }

    private static function applyRateLimit(array &$state, int $now): void
    {
        $bucket = $state['minute_bucket'] ?? ['start' => $now, 'count' => 0];
        $start = (int) ($bucket['start'] ?? $now);
        if ($now - $start >= 60) {
            $bucket = ['start' => $now, 'count' => 0];
        }

        $bucket['count'] = (int) ($bucket['count'] ?? 0) + 1;
        $state['minute_bucket'] = $bucket;

        if ((int) $bucket['count'] > 240) {
            $state['strikes'] = (int) ($state['strikes'] ?? 0) + 3;
            $state['ban_until'] = max((int) ($state['ban_until'] ?? 0), $now + 3600);
            $state['last_reason'] = 'rate limit';
            self::logEvent(self::clientIp(), 'rate_limit', 'Trop de requetes par minute');
        }
    }

    private static function detectSignals(string $payload): array
    {
        $signals = [];
        $text = mb_strtolower($payload);

        if ($text === '') {
            return $signals;
        }

        $hackPatterns = [
            '/\bsqlmap\b|\bnmap\b|\bnikto\b|\bacunetix\b/i',
            '/union\s+select|information_schema|sleep\s*\(|benchmark\s*\(|or\s+1\s*=\s*1/i',
            '/<script\b|onerror\s*=|php:\/\/|base64_decode\s*\(|shell_exec\s*\(|system\s*\(/i',
            '/\.\.\/\.\.\/|%2e%2e%2f|\/etc\/passwd|drop\s+table|insert\s+into/i',
        ];

        foreach ($hackPatterns as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $signals[] = ['type' => 'hack', 'points' => 4, 'ban' => 24 * 3600, 'reason' => 'Tentative de hacking detectee'];
                break;
            }
        }

        $hasUrl = preg_match('/https?:\/\//i', $text) === 1;
        $hasCredentialTrap = preg_match('/mot\s*de\s*passe|password|iban|carte|otp|code\s*2fa|seed/i', $text) === 1;
        $hasUrgencyBrand = preg_match('/paypal|stripe|banque|impots|caf|ameli/i', $text) === 1
            && preg_match('/urgent|verification|suspendu|compte\s*bloque|confirmez/i', $text) === 1;

        if (($hasUrl && $hasCredentialTrap) || $hasUrgencyBrand) {
            $signals[] = ['type' => 'phishing', 'points' => 3, 'ban' => 12 * 3600, 'reason' => 'Tentative de phishing detectee'];
        }

        $racismPatterns = [
            '/haine\s+raciale|appel\s+a\s+la\s+haine/i',
            '/race\s+inferieure|nettoyage\s+ethnique/i',
            '/violence\s+contre\s+les\s+\w+|expulser\s+tous\s+les\s+\w+/i',
        ];

        foreach ($racismPatterns as $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $signals[] = ['type' => 'racisme', 'points' => 6, 'ban' => 7 * 24 * 3600, 'reason' => 'Contenu raciste detecte'];
                break;
            }
        }

        return $signals;
    }

    private static function applySignal(array &$state, array $signal, int $now): void
    {
        $state['strikes'] = (int) ($state['strikes'] ?? 0) + (int) ($signal['points'] ?? 1);
        $banDuration = (int) ($signal['ban'] ?? 0);
        if ($banDuration > 0) {
            $state['ban_until'] = max((int) ($state['ban_until'] ?? 0), $now + $banDuration);
        }
        $state['last_reason'] = (string) ($signal['reason'] ?? 'signal securite');
        $state['last_seen_at'] = $now;

        self::logEvent(self::clientIp(), (string) ($signal['type'] ?? 'signal'), (string) ($signal['reason'] ?? ''));        
    }

    private static function collectPayload(): string
    {
        $chunks = [];
        $sources = [
            (string) ($_SERVER['REQUEST_URI'] ?? ''),
            (string) ($_SERVER['QUERY_STRING'] ?? ''),
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
            json_encode($_GET, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
            json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
        ];

        foreach ($sources as $source) {
            if ($source !== '') {
                $chunks[] = $source;
            }
        }

        return implode("\n", $chunks);
    }

    private static function storageRoot(): string
    {
        return dirname(__DIR__, 2) . self::STORAGE_DIR;
    }

    private static function ensureStorage(): void
    {
        $root = self::storageRoot();
        $states = $root . '/states';
        if (!is_dir($states)) {
            @mkdir($states, 0775, true);
        }
    }

    private static function statePath(string $ip): string
    {
        return self::storageRoot() . '/states/' . sha1($ip) . '.json';
    }

    private static function loadState(string $ip): array
    {
        self::ensureStorage();
        $path = self::statePath($ip);
        if (!is_file($path)) {
            return [
                'strikes' => 0,
                'ban_until' => 0,
                'minute_bucket' => ['start' => time(), 'count' => 0],
                'last_reason' => null,
                'last_seen_at' => time(),
            ];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [
                'strikes' => 0,
                'ban_until' => 0,
                'minute_bucket' => ['start' => time(), 'count' => 0],
                'last_reason' => null,
                'last_seen_at' => time(),
            ];
        }

        return $decoded;
    }

    private static function saveState(string $ip, array $state): void
    {
        self::ensureStorage();
        $path = self::statePath($ip);
        file_put_contents($path, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function logEvent(string $ip, string $kind, string $message): void
    {
        self::ensureStorage();
        $line = sprintf(
            "%s\tip=%s\tkind=%s\tmsg=%s\n",
            date('c'),
            $ip,
            $kind,
            str_replace(["\r", "\n", "\t"], ' ', $message)
        );
        @file_put_contents(self::storageRoot() . '/events.log', $line, FILE_APPEND);
    }

    private static function clientIp(): string
    {
        $candidates = [
            (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
            (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ];

        foreach ($candidates as $raw) {
            if ($raw === '') {
                continue;
            }
            $parts = array_map('trim', explode(',', $raw));
            foreach ($parts as $part) {
                if (filter_var($part, FILTER_VALIDATE_IP)) {
                    return $part;
                }
            }
        }

        return '0.0.0.0';
    }

    private static function deny(string $message): void
    {
        if (!headers_sent()) {
            http_response_code(403);
        }

        $errorPage = dirname(__DIR__, 2) . '/public/errors/403.php';
        if (is_file($errorPage)) {
            require $errorPage;
            exit;
        }

        echo '403 - ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }
}
