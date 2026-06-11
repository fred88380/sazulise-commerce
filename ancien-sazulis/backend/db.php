<?php
declare(strict_types=1);

// Mode dev (à désactiver en prod)
if (!headers_sent()) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}
error_reporting(E_ALL);

require_once __DIR__ . '/env.php';

// IMPORTANT: évite d’utiliser des anciennes valeurs définies ailleurs (protect.php, etc.)
unset($host, $db, $user, $pass, $charset);

$envPath = realpath(__DIR__ . '/../.env');
if (!$envPath) {
    http_response_code(500);
    exit("Erreur: .env introuvable (attendu: " . htmlspecialchars(__DIR__ . '/../.env') . ")");
}

$env = load_env($envPath);

// Adapte à TON .env (recommandé)
$host    = $env['DB_HOST'] ?? null;
$db      = $env['DB_NAME'] ?? null;
$user    = $env['DB_USER'] ?? null;
$pass    = $env['DB_PASS'] ?? null;
$charset = $env['DB_CHARSET'] ?? 'utf8mb4';

if (!$host || !$db || !$user || $pass === null) {
    http_response_code(500);
    exit("Erreur .env: DB_HOST/DB_NAME/DB_USER/DB_PASS manquants");
}

$dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    // en prod: log + message générique
    exit("Erreur PDO: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

/* ====== Helpers optionnels ====== */

function tableExists(PDO $pdo, string $table): bool {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->rowCount() > 0;
    } catch (Throwable) {
        return false;
    }
}

function getTableColumns(PDO $pdo, string $table): array {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `" . str_replace('`', '``', $table) . "`");
        $cols = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cols[] = $row['Field'];
        }
        return $cols;
    } catch (Throwable) {
        return [];
    }
}

function pickColumn(array $columns, array $candidates): ?string {
    foreach ($candidates as $cand) {
        if (in_array($cand, $columns, true)) return $cand;
    }
    return null;
}
