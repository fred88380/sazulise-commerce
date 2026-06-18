<?php

declare(strict_types=1);

namespace App\Core;

final class SecurityHeaders
{
    public static function setSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff', true);
        header('X-Frame-Options: DENY', true);
        header('X-XSS-Protection: 1; mode=block', true);
        header('Referrer-Policy: strict-origin-when-cross-origin', true);
        header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' cdn.jsdelivr.net; img-src \'self\' data: https:; font-src \'self\' cdn.jsdelivr.net; connect-src \'self\'; frame-ancestors \'none\'; base-uri \'self\'; form-action \'self\'', true);
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload', true);
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()', true);
        header('X-Permitted-Cross-Domain-Policies: none', true);
    }

    public static function configureSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE && session_status() !== PHP_SESSION_DISABLED) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_secure', '1');
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.gc_maxlifetime', '3600');
            ini_set('session.sid_length', '64');
            ini_set('session.sid_bits_per_character', '6');
            ini_set('session.entropy_file', '/dev/urandom');
            ini_set('session.entropy_length', '256');

            session_start();
        }
    }

    public static function validateSessionIntegrity(): bool
    {
        if (!isset($_SESSION['_security_signature'])) {
            $_SESSION['_security_signature'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
            return true;
        }

        $currentSignature = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
        return hash_equals($_SESSION['_security_signature'], $currentSignature);
    }

    public static function regenerateSessionIfOld(int $maxAge = 1800): void
    {
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
            return;
        }

        if ((time() - $_SESSION['_created']) > $maxAge) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    public static function preventCaching(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
        header('Pragma: no-cache', true);
        header('Expires: Thu, 01 Jan 1970 00:00:00 GMT', true);
    }

    public static function enableClickjackingProtection(): void
    {
        header('X-Frame-Options: DENY', true);
        header('Content-Security-Policy: frame-ancestors \'none\'', true);
    }

    public static function setNoIndexHeaders(): void
    {
        header('X-Robots-Tag: noindex, nofollow', true);
        header('X-UA-Compatible: IE=edge', true);
    }
}
