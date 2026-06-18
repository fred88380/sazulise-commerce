<?php

declare(strict_types=1);

namespace App\Core;

final class SecurityShield
{
    private const STORE_FILE = '/storage/security/ip_reputation.json';
    private const LOG_FILE = '/storage/security/security_events.log';

    public static function enforce(string $projectRoot): void
    {
        $ip = self::clientIp();
        if (self::isTrustedLocalIp($ip)) {
            return;
        }

        $storage = self::loadStore($projectRoot);
        $now = time();

        if (self::isCurrentlyBanned($storage, $ip, $now)) {
            self::logEvent($projectRoot, $ip, 'banned_request', ['uri' => self::requestUri()]);
            self::deny($projectRoot, 403);
        }

        $signals = self::detectSignals();
        if ($signals === []) {
            return;
        }

        $severity = array_sum(array_column($signals, 'weight'));
        $reasonLabels = array_values(array_unique(array_map(static fn (array $signal): string => (string) $signal['reason'], $signals)));

        $entry = $storage[$ip] ?? [
            'score' => 0,
            'attempts' => 0,
            'ban_until' => 0,
            'history' => [],
        ];

        $entry['score'] = (int) ($entry['score'] ?? 0) + $severity;
        $entry['attempts'] = (int) ($entry['attempts'] ?? 0) + 1;
        $entry['history'][] = [
            'time' => gmdate('c', $now),
            'uri' => self::requestUri(),
            'reasons' => $reasonLabels,
            'score' => $severity,
        ];
        $entry['history'] = array_slice((array) $entry['history'], -20);

        $banSeconds = self::banDuration($entry['score'], $entry['attempts']);
        if ($banSeconds > 0) {
            $entry['ban_until'] = $now + $banSeconds;
        }

        $storage[$ip] = $entry;
        self::saveStore($projectRoot, $storage);
        self::logEvent($projectRoot, $ip, 'malicious_request', [
            'uri' => self::requestUri(),
            'reasons' => $reasonLabels,
            'score' => $severity,
            'ban_until' => $entry['ban_until'] ?? 0,
        ]);

        self::deny($projectRoot, 403);
    }

    private static function detectSignals(): array
    {
        $signals = [];

        $uri = rawurldecode((string) ($_SERVER['REQUEST_URI'] ?? ''));
        $query = rawurldecode((string) ($_SERVER['QUERY_STRING'] ?? ''));
        $userAgent = rawurldecode((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $post = self::flatten($_POST);
        $json = self::jsonBody();
        $body = mb_strtolower(trim(implode("\n", array_filter([$uri, $query, $userAgent, $post, $json]))));

        foreach (self::hackPatterns() as $pattern => [$reason, $weight]) {
            if (preg_match($pattern, $body) === 1) {
                $signals[] = ['reason' => $reason, 'weight' => $weight];
            }
        }

        foreach (self::phishingPatterns() as $pattern => [$reason, $weight]) {
            if (preg_match($pattern, $body) === 1) {
                $signals[] = ['reason' => $reason, 'weight' => $weight];
            }
        }

        foreach (self::hateSpeechPatterns() as $pattern => [$reason, $weight]) {
            if (preg_match($pattern, $body) === 1) {
                $signals[] = ['reason' => $reason, 'weight' => $weight];
            }
        }

        return $signals;
    }

    private static function hackPatterns(): array
    {
        return [
            '/(?:union(?:\s+all)?\s+select|sleep\s*\(|benchmark\s*\(|drop\s+table|information_schema|into\s+outfile)/i' => ['sql_injection', 5],
            '/(?:<script\b|javascript:|onerror\s*=|onload\s*=|document\.cookie|alert\s*\()/i' => ['xss_payload', 5],
            '/(?:\.\/.+\.\./|\.env|/etc/passwd|boot\.ini|win\.ini)/i' => ['path_traversal', 5],
            '/(?:base64_decode\s*\(|shell_exec\s*\(|exec\s*\(|passthru\s*\(|system\s*\(|cmd\.exe|powershell\.exe)/i' => ['rce_payload', 6],
            '/(?:nmap|sqlmap|nikto|acunetix|masscan|gobuster|wpscan|dirbuster)/i' => ['scanner_signature', 4],
            '/(?:\$\{jndi:|ldap://|rmi://|dns://)/i' => ['log4shell_probe', 7],
            '/(?:\bselect\b.{0,20}\bfrom\b.{0,20}\busers\b)/i' => ['credential_harvest_probe', 4],
        ];
    }

    private static function phishingPatterns(): array
    {
        return [
            '/(?:seed\s*phrase|recovery\s*phrase|wallet\s*connect|metamask\s*support|private\s*key)/i' => ['phishing_wallet', 6],
            '/(?:confirm\s+your\s+password|verify\s+your\s+bank|urgent\s+account\s+verification|login\s+to\s+avoid\s+suspension)/i' => ['phishing_credentials', 5],
            '/(?:iban|credit\s*card|card\s*number|cvv|cryptomonnaie|crypto\s*wallet).{0,80}(?:envoyer|submit|confirm|urgent)/i' => ['phishing_financial', 5],
            '/(?:paypal|stripe|banque|impots|caf|ameli).{0,80}(?:mot\s*de\s*passe|password|identifiant|code\s*secret)/i' => ['phishing_brand_impersonation', 5],
        ];
    }

    private static function hateSpeechPatterns(): array
    {
        return [
            '/\b(?:nazi|hitler|kkk|white\s*power)\b/i' => ['extremist_content', 5],
            '/\b(?:sale\s+noir|sale\s+arabe|sale\s+juif|sale\s+blanc|bougnoule|negro|nigger|youpin|bicot|chinetoque)\b/i' => ['racist_slur', 8],
            '/\b(?:expulser\s+les|haine\s+des|supprimer\s+les|tuer\s+les).{0,40}(?:noirs|arabes|juifs|blancs|musulmans|chretiens|asiatiques|etrangers)\b/i' => ['violent_hate_speech', 9],
        ];
    }

    private static function banDuration(int $score, int $attempts): int
    {
        if ($score >= 18 || $attempts >= 5) {
            return 60 * 60 * 24 * 30;
        }

        if ($score >= 12 || $attempts >= 3) {
            return 60 * 60 * 24;
        }

        if ($score >= 6) {
            return 60 * 60;
        }

        return 15 * 60;
    }

    private static function isCurrentlyBanned(array $storage, string $ip, int $now): bool
    {
        return isset($storage[$ip]['ban_until']) && (int) $storage[$ip]['ban_until'] > $now;
    }

    private static function deny(string $projectRoot, int $status): void
    {
        if (!headers_sent()) {
            http_response_code($status);
        }

        $errorPage = $projectRoot . '/public/errors/' . $status . '.php';
        if (is_file($errorPage)) {
            require $errorPage;
            exit;
        }

        echo $status . ' - Access denied';
        exit;
    }

    private static function clientIp(): string
    {
        $candidates = [
            (string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
            (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
            (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '') {
                continue;
            }

            foreach (explode(',', $candidate) as $part) {
                $part = trim($part);
                if (filter_var($part, FILTER_VALIDATE_IP)) {
                    return $part;
                }
            }
        }

        return 'unknown';
    }

    private static function isTrustedLocalIp(string $ip): bool
    {
        return in_array($ip, ['127.0.0.1', '::1', 'unknown'], true)
            || str_starts_with($ip, '192.168.')
            || str_starts_with($ip, '10.')
            || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip) === 1;
    }

    private static function requestUri(): string
    {
        return (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' ' . (string) ($_SERVER['REQUEST_URI'] ?? '/');
    }

    private static function flatten(mixed $value): string
    {
        if (is_array($value)) {
            return implode(' ', array_map(static fn (mixed $item): string => self::flatten($item), $value));
        }

        if (is_scalar($value) || $value === null) {
            return (string) $value;
        }

        return '';
    }

    private static function jsonBody(): string
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        if (!str_contains($contentType, 'application/json')) {
            return '';
        }

        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            return '';
        }

        return $raw;
    }

    private static function loadStore(string $projectRoot): array
    {
        $file = $projectRoot . self::STORE_FILE;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if (!is_file($file)) {
            return [];
        }

        $raw = file_get_contents($file);
        $data = json_decode((string) $raw, true);
        return is_array($data) ? $data : [];
    }

    private static function saveStore(string $projectRoot, array $storage): void
    {
        $file = $projectRoot . self::STORE_FILE;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($file, json_encode($storage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private static function logEvent(string $projectRoot, string $ip, string $type, array $context = []): void
    {
        $file = $projectRoot . self::LOG_FILE;
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $line = json_encode([
            'time' => gmdate('c'),
            'ip' => $ip,
            'type' => $type,
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (is_string($line)) {
            file_put_contents($file, $line . PHP_EOL, FILE_APPEND);
        }
    }
}