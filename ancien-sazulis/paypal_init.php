<?php
// paypal_init.php
declare(strict_types=1);

/**
 * Charge le .env (si présent) dans $_ENV
 * (sans dépendance externe)
 */
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // retire guillemets éventuels
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$name] = $value;
    }
}

// Variables utiles
$paypalClientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
$paypalSecret   = $_ENV['PAYPAL_SECRET'] ?? '';
$paypalMode     = strtolower(trim($_ENV['PAYPAL_MODE'] ?? ($_ENV['PAYPAL_ENV'] ?? 'live'))); // live|sandbox
$paypalMode     = in_array($paypalMode, ['sandbox', 'live'], true) ? $paypalMode : 'live';

// Base API selon l'environnement
$paypalApiBase  = ($paypalMode === 'sandbox')
    ? 'https://api-m.sandbox.paypal.com'
    : 'https://api-m.paypal.com';

// Optionnel: expose aussi via getenv (pratique si tu utilises getenv ailleurs)
if ($paypalClientId) putenv('PAYPAL_CLIENT_ID=' . $paypalClientId);
if ($paypalSecret)   putenv('PAYPAL_SECRET=' . $paypalSecret);
if ($paypalMode)     putenv('PAYPAL_MODE=' . $paypalMode);
if ($paypalApiBase)  putenv('PAYPAL_API_BASE=' . $paypalApiBase);
