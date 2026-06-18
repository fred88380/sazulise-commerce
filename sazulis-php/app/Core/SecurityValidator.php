<?php

declare(strict_types=1);

namespace App\Core;

final class SecurityValidator
{
    private const SUSPICIOUS_PATTERNS = [
        'javascript:',
        'data:',
        'vbscript:',
        'on\w+\s*=',
        '<\s*script',
        '<\s*iframe',
        '<\s*object',
        '<\s*embed',
        '<\s*svg.*onload',
        '<!entity',
        '<%',
        '%>',
        '<\?',
        '\?>'
    ];

    private const HTML_DANGEROUS_TAGS = [
        'script',
        'iframe',
        'object',
        'embed',
        'applet',
        'meta',
        'link',
        'style',
        'svg',
        'form',
        'input',
        'button'
    ];

    public static function sanitizeText(string $text, int $maxLength = 500): string
    {
        $text = mb_substr(trim($text), 0, $maxLength, 'UTF-8');
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return $text;
    }

    public static function sanitizeHtml(string $html): string
    {
        $html = strip_tags($html);
        return self::sanitizeText($html);
    }

    public static function sanitizeEmail(string $email): ?string
    {
        $email = mb_strtolower(trim($email), 'UTF-8');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        if (strlen($email) > 254) {
            return null;
        }

        if (stripos($email, 'localhost') !== false ||
            stripos($email, '127.0.0.1') !== false ||
            stripos($email, '0.0.0.0') !== false) {
            return null;
        }

        $domain = substr($email, strrpos($email, '@') + 1);
        if (!preg_match('/^[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?)*$/i', $domain)) {
            return null;
        }

        return $email;
    }

    public static function validatePassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 12) {
            $errors[] = 'Le mot de passe doit contenir au moins 12 caractères.';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return $errors;
    }

    public static function isXssSuspicious(string $text): bool
    {
        $text = mb_strtolower($text, 'UTF-8');

        foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
            if (preg_match('/' . $pattern . '/i', $text)) {
                return true;
            }
        }

        foreach (self::HTML_DANGEROUS_TAGS as $tag) {
            if (stripos($text, '<' . $tag) !== false) {
                return true;
            }
        }

        if (preg_match('/&#\d{4,};/', $text) || preg_match('/&#x[0-9a-f]{4,};/i', $text)) {
            return true;
        }

        return false;
    }

    public static function sanitizePath(string $path): string
    {
        $path = trim($path);
        $path = preg_replace('/\.\.\/|\.\.\\\\/', '', $path) ?? $path;
        $path = preg_replace('/\/+/', '/', $path) ?? $path;

        return $path;
    }

    public static function sanitizeSql(string $text): string
    {
        $dangerous = ['\'', '"', '\\', ';', '--', '/*', '*/', 'xp_', 'sp_', 'exec', 'execute'];

        $text = str_ireplace($dangerous, '', $text);
        $text = trim($text);

        return $text;
    }

    public static function validateUrl(string $url, array $allowedSchemes = ['https', 'http']): ?string
    {
        $url = trim($url);

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parsed = parse_url($url);
        if (!isset($parsed['scheme'])) {
            return null;
        }

        if (!in_array(mb_strtolower($parsed['scheme']), array_map('mb_strtolower', $allowedSchemes), true)) {
            return null;
        }

        if (isset($parsed['host'])) {
            if (!filter_var($parsed['host'], FILTER_VALIDATE_DOMAIN,
                FILTER_FLAG_HOSTNAME) && !filter_var($parsed['host'], FILTER_VALIDATE_IP)) {
                return null;
            }
        }

        return filter_var($url, FILTER_SANITIZE_URL);
    }

    public static function sanitizePhoneNumber(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9+\-\s().]/', '', $phone) ?? '';
        $phone = trim($phone);

        if (strlen($phone) < 7 || strlen($phone) > 20) {
            return null;
        }

        if (!preg_match('/^[0-9+\-\s()().]*$/', $phone)) {
            return null;
        }

        return $phone;
    }

    public static function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._\-]/', '_', $filename) ?? '';
        $filename = preg_replace('/_+/', '_', $filename) ?? '';
        $filename = trim($filename, '_');

        if (strlen($filename) > 255) {
            $pathinfo = pathinfo($filename);
            $name = mb_substr($pathinfo['filename'] ?? '', 0, 200, 'UTF-8');
            $ext = $pathinfo['extension'] ?? '';
            $filename = $name . ($ext ? '.' . $ext : '');
        }

        return $filename;
    }

    public static function escapeJson(string $text): string
    {
        $json = json_encode($text, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false ? $json : '""';
    }

    public static function validateIpAddress(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    public static function isPrivateIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    public static function sanitizeInteger(mixed $value, int $min = 0, int $max = PHP_INT_MAX): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        if ($int < $min || $int > $max) {
            return null;
        }

        return $int;
    }

    public static function sanitizeFloat(mixed $value, float $min = 0.0, float $max = PHP_FLOAT_MAX): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        if ($float < $min || $float > $max) {
            return null;
        }

        return $float;
    }

    public static function validateFileUpload(array $file, array $allowedMimes, int $maxSize): array
    {
        $errors = [];

        if (!isset($file['tmp_name'], $file['name'], $file['size'], $file['error'])) {
            $errors[] = 'Fichier invalide.';
            return $errors;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Erreur lors de l\'upload du fichier.';
            return $errors;
        }

        $filename = self::sanitizeFilename((string) $file['name']);
        if ($filename === '') {
            $errors[] = 'Nom de fichier invalide.';
        }

        $size = (int) $file['size'];
        if ($size > $maxSize) {
            $errors[] = 'Le fichier dépasse la taille maximale autorisée.';
        }

        if ($size < 10) {
            $errors[] = 'Le fichier est trop petit.';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimes, true)) {
            $errors[] = 'Type de fichier non autorisé.';
        }

        return $errors;
    }

    public static function detectSqlInjection(string $text): bool
    {
        $patterns = [
            '/union\s+select/i',
            '/select\s+.*\s+from/i',
            '/insert\s+into/i',
            '/update\s+.*\s+set/i',
            '/delete\s+from/i',
            '/drop\s+table/i',
            '/exec\s*\(/i',
            '/execute\s*\(/i',
            '/;\s*(drop|delete|update|insert|exec|execute)/i',
            '/--\s*$/m',
            '/\/\*.*\*\//s'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    public static function sanitizeHeaderValue(string $value): string
    {
        $value = trim($value);
        $value = str_replace(["\r", "\n"], '', $value);

        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public static function validateCreditCard(string $cardNumber): bool
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber) ?? '';

        if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
            return false;
        }

        $sum = 0;
        $isEven = false;

        for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
            $n = (int) $cardNumber[$i];

            if ($isEven) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }

            $sum += $n;
            $isEven = !$isEven;
        }

        return $sum % 10 === 0;
    }

    public static function isSuspiciousActivity(array $sessionData, array $currentData): bool
    {
        if (!isset($sessionData['last_activity_ip'], $currentData['ip'])) {
            return false;
        }

        if ($sessionData['last_activity_ip'] !== $currentData['ip']) {
            return true;
        }

        if (isset($sessionData['last_activity_time'])) {
            $timeDiff = time() - (int) $sessionData['last_activity_time'];
            if ($timeDiff > 86400) {
                return true;
            }
        }

        return false;
    }
}
