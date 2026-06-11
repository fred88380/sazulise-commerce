<?php
/**
 * Charge un fichier .env (format KEY=VALUE) et retourne un tableau associatif.
 * Supporte:
 *  - lignes vides
 *  - commentaires # ou ;
 *  - valeurs entre guillemets "..."
 */
function load_env(string $path): array
{
    if (!is_readable($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        $line = trim($line);

        // commentaires / lignes vides
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }

        // split KEY=VALUE
        $pos = strpos($line, '=');
        if ($pos === false) continue;

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        // retire guillemets simples/doubles
        $val = trim($val, " \t\n\r\0\x0B\"'");

        $env[$key] = $val;
    }

    return $env;
}
