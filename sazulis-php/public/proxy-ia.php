<?php

declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
@set_time_limit(300);
@ini_set('memory_limit', '512M');

if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', '1');
}
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
while (@ob_get_level()) {
    @ob_end_clean();
}

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

register_shutdown_function(static function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (@ob_get_level()) {
            @ob_end_clean();
        }
        echo json_encode(['ok' => false, 'error' => 'PHP fatal: ' . $err['message'] . ' ligne ' . $err['line']]);
    }
});

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::getConnection();
if (!$pdo) {
    echo json_encode(['ok' => false, 'error' => 'Base de donnees indisponible']);
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function ensureProspectionTables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_blacklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        motif VARCHAR(100) DEFAULT 'manuel',
        date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS prospects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        site_url VARCHAR(500) DEFAULT NULL,
        source VARCHAR(100) DEFAULT 'Sazulis IA',
        statut VARCHAR(50) DEFAULT 'nouveau',
        mail_envoye TINYINT(1) NOT NULL DEFAULT 0,
        date_mail DATETIME DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function readApiKey(): string
{
    return trim((string) ($_ENV['CLE-API'] ?? getenv('CLE-API') ?: ''));
}

function detectProvider(string $key): string
{
    if (str_starts_with($key, 'AIza')) {
        return 'gemini';
    }
    if (str_starts_with($key, 'gsk_')) {
        return 'groq';
    }
    if (str_starts_with($key, 'sk-ant')) {
        return 'anthropic';
    }
    return 'openrouter';
}

function doGet(string $url, int $timeout = 5): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = (string) curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => (string) $resp, 'error' => $err];
}

function doPost(string $url, array $headers, string $payload, int $timeout = 25): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = (string) curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => (string) $resp, 'error' => $err];
}

function isEmailBlacklisted(PDO $pdo, string $email): bool
{
    if ($email === '') {
        return false;
    }
    $stmt = $pdo->prepare('SELECT 1 FROM email_blacklist WHERE email = ?');
    $stmt->execute([$email]);
    return (bool) $stmt->fetchColumn();
}

function addToBlacklist(PDO $pdo, string $email, string $motif = 'manuel'): bool
{
    if ($email === '') {
        return false;
    }
    $stmt = $pdo->prepare('INSERT IGNORE INTO email_blacklist (email, motif) VALUES (?, ?)');
    return $stmt->execute([$email, $motif]);
}

function evaluerVetusteSite(string $html, string $url): array
{
    $score = 0;
    $anomalies = [];

    if (stripos($html, 'name="viewport"') === false && stripos($html, "name='viewport'") === false) {
        $score += 35;
        $anomalies[] = 'Pas de viewport mobile';
    }

    if (!str_starts_with($url, 'https://') && substr_count($html, 'https://') < 3) {
        $score += 20;
        $anomalies[] = 'HTTPS peu present';
    }

    if (preg_match('/©\s*(200[0-9]|201[0-8])/', $html, $m)) {
        $score += 20;
        $anomalies[] = 'Copyright ancien (' . $m[1] . ')';
    }

    if (stripos($html, '<frameset>') !== false || stripos($html, '<frame ') !== false) {
        $score += 25;
        $anomalies[] = 'Usage de frames obsoletes';
    } elseif (substr_count($html, '<table') > 12 && substr_count($html, '<div') < 20) {
        $score += 20;
        $anomalies[] = 'Mise en page table legacy';
    }

    if (stripos($html, 'rel="canonical"') === false && stripos($html, "rel='canonical'") === false) {
        $score += 15;
        $anomalies[] = 'Balise canonical absente';
    }

    if (stripos($html, 'property="og:title"') === false && stripos($html, "property='og:title'") === false) {
        $score += 10;
        $anomalies[] = 'Open Graph absent';
    }

    if (preg_match('/jquery[-.]1\.[0-9]/i', $html)) {
        $score += 15;
        $anomalies[] = 'jQuery v1 detecte';
    }

    if (stripos($html, 'wp-content') !== false || stripos($html, 'next/script') !== false || stripos($html, '_nuxt/') !== false) {
        $score = max(0, $score - 35);
    }

    $score = min(100, $score);

    return [
        'score' => $score,
        'anomalies' => $anomalies,
        'critique' => $score >= 25,
    ];
}

ensureProspectionTables($pdo);
$action = trim((string) ($_POST['action'] ?? ''));

if ($action === 'ia_proxy') {
    $prompt = trim((string) ($_POST['prompt'] ?? ''));
    if ($prompt === '') {
        echo json_encode(['ok' => false, 'error' => 'Prompt vide']);
        exit;
    }

    $key = readApiKey();
    if ($key === '') {
        echo json_encode(['ok' => false, 'error' => 'CLE-API absente dans .env']);
        exit;
    }

    $provider = trim((string) ($_POST['provider'] ?? ''));
    if ($provider === '') {
        $provider = detectProvider($key);
    }

    $jsonH = ['Content-Type: application/json'];

    if ($provider === 'gemini') {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . rawurlencode($key);
        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['maxOutputTokens' => 1500, 'temperature' => 0.9],
        ]);
        $r = doPost($url, $jsonH, (string) $payload);
        if ($r['error'] !== '') {
            echo json_encode(['ok' => false, 'error' => 'cURL: ' . $r['error']]);
            exit;
        }
        $d = json_decode($r['body'], true);
        if ($r['code'] !== 200) {
            echo json_encode(['ok' => false, 'error' => $d['error']['message'] ?? ('HTTP ' . $r['code'])]);
            exit;
        }
        echo json_encode(['ok' => true, 'text' => $d['candidates'][0]['content']['parts'][0]['text'] ?? '']);
        exit;
    }

    if ($provider === 'groq') {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $payload = json_encode([
            'model' => 'llama-3.3-70b-versatile',
            'max_tokens' => 1500,
            'temperature' => 0.9,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);
        $r = doPost($url, array_merge($jsonH, ['Authorization: Bearer ' . $key]), (string) $payload);
        if ($r['error'] !== '') {
            echo json_encode(['ok' => false, 'error' => 'cURL: ' . $r['error']]);
            exit;
        }
        $d = json_decode($r['body'], true);
        if ($r['code'] !== 200) {
            echo json_encode(['ok' => false, 'error' => $d['error']['message'] ?? ('HTTP ' . $r['code'])]);
            exit;
        }
        echo json_encode(['ok' => true, 'text' => $d['choices'][0]['message']['content'] ?? '']);
        exit;
    }

    $orHeaders = array_merge($jsonH, [
        'Authorization: Bearer ' . $key,
        'HTTP-Referer: ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    ]);
    $models = [
        'meta-llama/llama-3.3-70b-instruct:free',
        'qwen/qwen3-8b:free',
        'mistralai/mistral-small-3.1-24b-instruct:free',
    ];

    foreach ($models as $model) {
        $payload = json_encode([
            'model' => $model,
            'max_tokens' => 1500,
            'temperature' => 0.9,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ]);
        $r = doPost('https://openrouter.ai/api/v1/chat/completions', $orHeaders, (string) $payload);
        if ($r['code'] === 200) {
            $d = json_decode($r['body'], true);
            $txt = $d['choices'][0]['message']['content'] ?? '';
            if ($txt !== '') {
                echo json_encode(['ok' => true, 'text' => $txt]);
                exit;
            }
        }
    }

    echo json_encode(['ok' => false, 'error' => 'Aucun modele IA disponible']);
    exit;
}

if ($action === 'blacklist_email') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    if ($email === '') {
        echo json_encode(['ok' => false, 'error' => 'Email manquant']);
        exit;
    }
    $email = preg_replace('/^probable:/', '', $email);
    $ok = addToBlacklist($pdo, $email, 'manuel');
    echo json_encode(['ok' => $ok, 'email' => $email]);
    exit;
}

if ($action === 'marquer_mail_envoye') {
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $site = trim((string) ($_POST['site'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($nom === '' && $email === '' && $site === '') {
        echo json_encode(['ok' => false, 'error' => 'Prospect vide']);
        exit;
    }

    $check = $pdo->prepare('SELECT id FROM prospects WHERE email = ? OR (nom = ? AND site_url = ?) LIMIT 1');
    $check->execute([$email ?: null, $nom, $site ?: null]);
    $existing = $check->fetchColumn();

    if ($existing) {
        $pdo->prepare("UPDATE prospects SET mail_envoye = 1, date_mail = NOW(), statut = 'contacte' WHERE id = ?")->execute([(int) $existing]);
        echo json_encode(['ok' => true, 'action' => 'updated']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO prospects (nom, email, site_url, source, statut, mail_envoye, date_mail, notes) VALUES (?, ?, ?, 'Sazulis IA', 'contacte', 1, NOW(), ?)");
    $stmt->execute([$nom !== '' ? $nom : 'Prospect', $email ?: null, $site ?: null, $notes !== '' ? $notes : null]);
    echo json_encode(['ok' => true, 'action' => 'created']);
    exit;
}

if ($action === 'get_contacted') {
    $rows = $pdo->query('SELECT nom, email, site_url, date_mail FROM prospects WHERE mail_envoye = 1 AND date_mail >= DATE_SUB(NOW(), INTERVAL 7 DAY)')->fetchAll();
    echo json_encode(['ok' => true, 'rows' => $rows]);
    exit;
}

if ($action === 'scraper_manual') {
    $urlsText = trim((string) ($_POST['urls'] ?? ''));
    if ($urlsText === '') {
        echo json_encode(['ok' => false, 'error' => 'Aucune URL fournie']);
        exit;
    }

    $urls = array_filter(array_map('trim', explode("\n", $urlsText)), static function (string $u): bool {
        return (bool) filter_var($u, FILTER_VALIDATE_URL);
    });

    if (empty($urls)) {
        echo json_encode(['ok' => false, 'error' => 'Aucune URL valide']);
        exit;
    }

    $results = [];
    foreach ($urls as $url) {
        $page = doGet($url, 8);
        if ($page['code'] !== 200 || $page['body'] === '') {
            $results[] = ['url' => $url, 'error' => 'HTTP ' . $page['code']];
            continue;
        }

        $html = $page['body'];
        preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $html, $m);
        $emails = array_values(array_unique(array_filter($m[0] ?? [], static function (string $e): bool {
            return !preg_match('/example|yourname|domain\\.com|\\.png|\\.jpg|\\.css|\\.js/i', $e);
        })));

        $audit = evaluerVetusteSite($html, $url);
        $results[] = [
            'nom' => parse_url($url, PHP_URL_HOST),
            'url' => $url,
            'emails' => $emails,
            'score' => $audit['score'],
            'anomalies' => $audit['anomalies'],
            'plateforme' => str_contains($html, 'wp-content') ? 'WordPress' : 'Custom',
        ];
    }

    echo json_encode(['ok' => true, 'results' => $results]);
    exit;
}

if ($action === 'scraper_auto') {
    $secteur = trim((string) ($_POST['secteur'] ?? ''));
    $ville = trim((string) ($_POST['ville'] ?? ''));
    $limit = min(100, max(5, (int) ($_POST['limit'] ?? 20)));

    if ($ville === '' || strtolower($ville) === 'france') {
        $cities = ['Paris','Marseille','Lyon','Toulouse','Nice','Nantes','Bordeaux','Lille','Rennes','Metz'];
        $ville = $cities[array_rand($cities)];
    }

    $geo = doGet('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . rawurlencode($ville), 5);
    $geoData = $geo['code'] === 200 ? (json_decode($geo['body'], true)[0] ?? []) : [];
    $lat = (float) ($geoData['lat'] ?? 0);
    $lon = (float) ($geoData['lon'] ?? 0);

    if ($lat === 0.0 || $lon === 0.0) {
        echo json_encode(['ok' => false, 'error' => 'Geolocalisation introuvable pour ' . $ville]);
        exit;
    }

    $radius = 15000;
    $query = '[out:json][timeout:25];(';
    if ($secteur !== '' && $secteur !== 'commerce') {
        $s = preg_replace('/[^a-z0-9_-]/i', '', $secteur);
        $query .= 'node["shop"~"' . $s . '",i](around:' . $radius . ',' . $lat . ',' . $lon . ');';
        $query .= 'node["office"~"' . $s . '",i](around:' . $radius . ',' . $lat . ',' . $lon . ');';
        $query .= 'node["craft"~"' . $s . '",i](around:' . $radius . ',' . $lat . ',' . $lon . ');';
    } else {
        $query .= 'node["shop"](around:' . $radius . ',' . $lat . ',' . $lon . ');';
        $query .= 'node["office"](around:' . $radius . ',' . $lat . ',' . $lon . ');';
        $query .= 'node["craft"](around:' . $radius . ',' . $lat . ',' . $lon . ');';
    }
    $query .= ');out body 350;';

    $osm = doPost(
        'https://overpass-api.de/api/interpreter',
        ['Content-Type: application/x-www-form-urlencoded', 'User-Agent: SazulisDashboard/1.0'],
        'data=' . rawurlencode($query),
        25
    );

    if ($osm['code'] !== 200 || $osm['body'] === '') {
        echo json_encode(['ok' => false, 'error' => 'Service cartographique indisponible']);
        exit;
    }

    $elements = json_decode($osm['body'], true)['elements'] ?? [];
    if (empty($elements)) {
        echo json_encode(['ok' => true, 'results' => []]);
        exit;
    }

    shuffle($elements);

    $prospects = [];
    $httpChecks = 0;
    $maxChecks = 140;

    foreach ($elements as $el) {
        if (count($prospects) >= $limit || $httpChecks >= $maxChecks) {
            break;
        }

        $tags = $el['tags'] ?? [];
        $name = trim((string) ($tags['name'] ?? ($tags['brand'] ?? '')));
        if ($name === '' || strlen($name) < 3) {
            continue;
        }

        $site = trim((string) ($tags['website'] ?? ($tags['contact:website'] ?? '')));
        if ($site === '' || !filter_var($site, FILTER_VALIDATE_URL)) {
            continue;
        }

        $httpChecks++;
        $page = doGet($site, 3);
        if ($page['code'] !== 200 || $page['body'] === '') {
            continue;
        }

        $html = $page['body'];
        $audit = evaluerVetusteSite($html, $site);
        if (!$audit['critique']) {
            continue;
        }

        $email = trim((string) ($tags['email'] ?? ($tags['contact:email'] ?? '')));
        if ($email === '') {
            if (preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i', $html, $m)) {
                $emails = array_unique($m[0]);
                foreach ($emails as $candidate) {
                    if (!preg_match('/\\.(png|jpg|jpeg|gif|svg|css|js)$/i', $candidate) && !preg_match('/(abuse|postmaster|noreply|wordpress|wix)/i', $candidate)) {
                        $email = $candidate;
                        break;
                    }
                }
            }
        }

        if ($email !== '' && isEmailBlacklisted($pdo, strtolower($email))) {
            continue;
        }

        $platform = 'Custom';
        $host = (string) parse_url($site, PHP_URL_HOST);
        if (str_contains($host, 'wix')) {
            $platform = 'Wix';
        } elseif (str_contains($host, 'jimdo')) {
            $platform = 'Jimdo';
        } elseif (str_contains($html, 'wp-content')) {
            $platform = 'WordPress';
        }

        $prospects[] = [
            'nom' => $name,
            'url' => $site,
            'emails' => $email !== '' ? [$email] : [],
            'plateforme' => $platform,
            'score' => $audit['score'],
            'anomalies' => $audit['anomalies'],
        ];
    }

    usort($prospects, static fn(array $a, array $b): int => ((int) $b['score']) <=> ((int) $a['score']));

    echo json_encode(['ok' => true, 'results' => $prospects]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Action inconnue']);
