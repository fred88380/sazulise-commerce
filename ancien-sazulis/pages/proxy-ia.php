<?php
// 1. Protection absolue du flux JSON : on cache les warnings textuels de PHP
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. Augmenter les limites du serveur au maximum (5 minutes d'exécution, 512M de RAM)
@set_time_limit(300); 
@ini_set('memory_limit', '512M');

// 3. Désactiver les buffers système pour fluidifier les échanges de données
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (@ob_get_level()) {
    @ob_end_clean();
}

// Forcer immédiatement les headers JSON avant tout traitement
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store');
}

// Gestion propre des erreurs fatales en JSON
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        while (@ob_get_level()) @ob_end_clean();
        echo json_encode(['error' => 'PHP Fatal: ' . $err['message'] . ' ligne ' . $err['line']]);
    }
});

require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo)) { 
    echo json_encode(['error' => 'DB indisponible']); 
    exit; 
}

// Création automatique de la table blacklist si elle n'existe pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_blacklist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        motif VARCHAR(100) DEFAULT 'manuel',
        date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Throwable $e) { /* ignorer */ }

/* ── Fonctions blacklist ── */
function isEmailBlacklisted(PDO $pdo, string $email): bool {
    if (empty($email)) return false;
    $stmt = $pdo->prepare("SELECT 1 FROM email_blacklist WHERE email = ?");
    $stmt->execute([$email]);
    return (bool)$stmt->fetchColumn();
}

function addToBlacklist(PDO $pdo, string $email, string $motif = 'manuel'): bool {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO email_blacklist (email, motif) VALUES (?, ?)");
        return $stmt->execute([$email, $motif]);
    } catch (Throwable $e) {
        return false;
    }
}

function cleanEmail(string $email): string {
    return preg_replace('/^probable:/', '', $email);
}

/**
 * MOTEUR D'AUDIT TECHNIQUE DE VÉTUSTÉ ET SEO
 * Analyse le code HTML d'un site pour évaluer son obsolescence et ses manques SEO.
 */
function evaluerVetusteSite(string $html, string $url): array {
    $score = 0;
    $anomalies = [];

    // 1. Manque de Responsive Design
    if (stripos($html, 'name="viewport"') === false && stripos($html, "name='viewport'") === false) {
        $score += 35;
        $anomalies[] = "Non adapté aux mobiles (Pas de balise viewport)";
    }

    // 2. Liens ou protocole non sécurisés
    if (strpos($url, 'https://') === false && substr_count($html, 'https://') < 3) {
        $score += 20;
        $anomalies[] = "Sécurité obsolète (Tout en HTTP ou manque de HTTPS)";
    }

    // 3. Présence d'un vieux Copyright figé dans le temps
    if (preg_match('/©\s*(200[0-9]|201[0-8])/', $html, $matches)) {
        $score += 20;
        $anomalies[] = "Mentions / Copyright obsolète (" . $matches[1] . ")";
    }

    // 4. Structure de code préhistorique
    if (stripos($html, '<frameset>') !== false || stripos($html, '<frame ') !== false) {
        $score += 25;
        $anomalies[] = "Utilisation de Frames / Cadres obsolètes";
    } elseif (substr_count($html, '<table') > 12 && substr_count($html, '<div') < 20) {
        $score += 20;
        $anomalies[] = "Mise en page archaïque construite par tableaux";
    }

    // 5. Analyse SEO Technique Avancée
    if (stripos($html, 'rel="canonical"') === false && stripos($html, "rel='canonical'") === false) {
        $score += 15;
        $anomalies[] = "Balise Canonique absente";
    }
    
    if (stripos($html, 'property="og:title"') === false && stripos($html, 'property=\'og:title\'') === false) {
        $score += 15;
        $anomalies[] = "Balises Open Graph (OG:Title) manquantes";
    }

    if (stripos($html, 'property="og:description"') === false && stripos($html, 'property=\'og:description\'') === false) {
        $score += 10;
        $anomalies[] = "Balise OG:Description manquante";
    }

    // 6. Technologies abandonnées ou vieilles librairies
    if (stripos($html, '.swf') !== false) {
        $score += 15;
        $anomalies[] = "Traces de technologie Flash (.swf)";
    }
    if (preg_match('/jquery[-.]1\.[0-9]/i', $html)) {
        $score += 15;
        $anomalies[] = "Version de jQuery obsolète et vulnérable";
    }

    if (stripos($html, 'next/script') !== false || stripos($html, '_nuxt/') !== false) {
        $score = max(0, $score - 40); 
    }

    if ($score > 100) $score = 100;

    // Seuil de tolérance élargi à 20 pour valider plus facilement les opportunités SEO/Dev
    return [
        'score' => $score,
        'anomalies' => $anomalies,
        'priorite' => ($score >= 50) ? "CRITIQUE" : "Moyenne",
        'critique' => ($score >= 20) 
    ];
}

/* ── Lecture .env pour la clé IA ── */
function readEnvKey(): string {
    $dir = __DIR__;
    for ($i = 0; $i < 4; $i++) {
        $f = $dir . '/.env';
        if (@file_exists($f)) {
            foreach (@file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if (strpos($line, 'CLE-API=') === 0) return trim(substr($line, 8));
            }
        }
        $parent = dirname($dir);
        if ($parent === $dir) break;
        $dir = $parent;
    }
    return '';
}

function detectProvider(string $key): string {
    if (strpos($key, 'AIza')   === 0) return 'gemini';
    if (strpos($key, 'gsk_')   === 0) return 'groq';
    if (strpos($key, 'sk-or-') === 0) return 'openrouter';
    if (strpos($key, 'sk-ant') === 0) return 'anthropic';
    return 'openrouter';
}

function doGet(string $url, int $timeout = 2): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 2,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => $resp, 'error' => $err];
}

function doPost(string $url, array $headers, string $payload, int $timeout = 25): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return ['code' => $code, 'body' => $resp, 'error' => $err];
}

$action = trim($_POST['action'] ?? '');

/* ══════════════════════════════════════
    ia_proxy – appel LLM
    ══════════════════════════════════════ */
if ($action === 'ia_proxy') {
    $prompt   = trim($_POST['prompt']   ?? '');
    $provider = trim($_POST['provider'] ?? '');
    if (!$prompt) { echo json_encode(['error' => 'Prompt vide']); exit; }

    $key = readEnvKey();
    if (!$key) { echo json_encode(['error' => 'Cle CLE-API introuvable dans .env']); exit; }
    if (!$provider) $provider = detectProvider($key);

    $jsonH = ['Content-Type: application/json'];

    if ($provider === 'gemini') {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=$key";
        $pl  = json_encode(['contents'=>[['parts'=>[['text'=>$prompt]]]],'generationConfig'=>['maxOutputTokens'=>1500,'temperature'=>0.9]]);
        $r   = doPost($url, $jsonH, $pl);
        if ($r['error']) { echo json_encode(['error'=>'cURL: '.$r['error']]); exit; }
        if ($r['code']!==200) { $d=json_decode($r['body'],true); echo json_encode(['error'=>$d['error']['message']??"HTTP {$r['code']}"]); exit; }
        $d = json_decode($r['body'],true);
        echo json_encode(['content'=>[['type'=>'text','text'=>$d['candidates'][0]['content']['parts'][0]['text']??'']]]);
        exit;
    }
    if ($provider === 'groq') {
        $url = 'https://api.groq.com/openai/v1/chat/completions';
        $pl  = json_encode(['model'=>'llama-3.3-70b-versatile','max_tokens'=>1500,'temperature'=>0.9,'messages'=>[['role'=>'user','content'=>$prompt]]]);
        $r   = doPost($url, array_merge($jsonH, ['Authorization: Bearer '.$key]), $pl);
        if ($r['error']) { echo json_encode(['error'=>'cURL: '.$r['error']]); exit; }
        if ($r['code']!==200) { $d=json_decode($r['body'],true); echo json_encode(['error'=>$d['error']['message']??"HTTP {$r['code']}"]); exit; }
        $d = json_decode($r['body'],true);
        echo json_encode(['content'=>[['type'=>'text','text'=>$d['choices'][0]['message']['content']??'']]]);
        exit;
    }
    if ($provider === 'anthropic') {
        $url = 'https://api.anthropic.com/v1/messages';
        $pl  = json_encode(['model'=>'claude-haiku-4-5-20251001','max_tokens'=>1500,'temperature'=>0.9,'messages'=>[['role'=>'user','content'=>$prompt]]]);
        $r   = doPost($url, array_merge($jsonH, ['x-api-key: '.$key,'anthropic-version: 2023-06-01']), $pl);
        if ($r['error']) { echo json_encode(['error'=>'cURL: '.$r['error']]); exit; }
        if ($r['code']!==200) { $d=json_decode($r['body'],true); echo json_encode(['error'=>$d['error']['message']??"HTTP {$r['code']}"]); exit; }
        echo $r['body'];
        exit;
    }
    // OpenRouter
    $url    = 'https://openrouter.ai/api/v1/chat/completions';
    $orH    = array_merge($jsonH, ['Authorization: Bearer '.$key, 'HTTP-Referer: '.($_SERVER['HTTP_HOST']??'localhost')]);
    $models = ['meta-llama/llama-3.3-70b-instruct:free','qwen/qwen3-8b:free','mistralai/mistral-small-3.1-24b-instruct:free','openrouter/free'];
    foreach ($models as $model) {
        $pl = json_encode(['model'=>$model,'max_tokens'=>1500,'temperature'=>0.9,'messages'=>[['role'=>'user','content'=>$prompt]]]);
        $r  = doPost($url, $orH, $pl);
        if ($r['code']===200) {
            $d = json_decode($r['body'],true);
            $t = $d['choices'][0]['message']['content']??'';
            if ($t) { echo json_encode(['content'=>[['type'=>'text','text'=>$t]]]); exit; }
        }
    }
    echo json_encode(['error'=>'Tous les modeles OpenRouter sont indisponibles.']);
    exit;
}

/* ══════════════════════════════════════
    enrich_prospect – OSM
    ══════════════════════════════════════ */
if ($action === 'enrich_prospect') {
    $nom  = trim($_POST['nom']  ?? '');
    $zone = trim($_POST['zone'] ?? '');
    if (!$nom) { echo json_encode(['ok'=>false,'error'=>'Nom manquant']); exit; }

    $geoR = doGet('https://nominatim.openstreetmap.org/search?format=json&limit=1&q='.urlencode($zone?:'France'), 5);
    $geo  = ($geoR['code']===200) ? (json_decode($geoR['body'],true)[0]??[]) : [];
    $lat  = (float)($geo['lat']??46.603354);
    $lon  = (float)($geo['lon']??1.888334);

    $n = preg_replace('/[^\w\s]/u', '', $nom);
    $q = '[out:json][timeout:8];(node["name"~"'.$n.'",i](around:15000,'.$lat.','.$lon.'););out body 3;';
    $r = doPost('https://overpass-api.de/api/interpreter',
        ['Content-Type: application/x-www-form-urlencoded','User-Agent: SazulisDashboard/1.0'],
        'data='.urlencode($q), 10);

    $site = $email = $phone = '';
    if ($r['code']===200) {
        foreach (json_decode($r['body'],true)['elements']??[] as $el) {
            $t=$el['tags']??[];
            $site=$t['website']??$t['contact:website']??'';
            $email=$t['email']??$t['contact:email']??'';
            $phone=$t['phone']??$t['contact:phone']??'';
            if ($site||$email) break;
        }
    }
    echo json_encode(['ok'=>true,'site'=>$site?:null,'email'=>$email?:null,'phone'=>$phone?:null]);
    exit;
}

/* ══════════════════════════════════════
    marquer_mail_envoye / get_contacted
    ══════════════════════════════════════ */
if ($action === 'marquer_mail_envoye' || $action === 'get_contacted') {
    if ($action === 'get_contacted') {
        try {
            $rows = $pdo->query("SELECT nom, email, site_url, date_mail FROM prospects WHERE mail_envoye=1 AND date_mail >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchAll(PDO::FETCH_ASSOC);
            try { $pdo->exec("UPDATE prospects SET mail_envoye=0,date_mail=NULL WHERE mail_envoye=1 AND date_mail < DATE_SUB(NOW(), INTERVAL 7 DAY)"); } catch(Throwable $e){}
            $c = ['email'=>[],'nom'=>[],'site'=>[],'dates'=>[]];
            foreach ($rows as $row) {
                if ($row['email'])    $c['email'][] = strtolower(trim($row['email']));
                if ($row['nom'])      $c['nom'][]   = strtolower(trim($row['nom']));
                if ($row['site_url']) $c['site'][]  = strtolower(trim($row['site_url']));
                $key = strtolower(trim($row['email']?:$row['nom']));
                if ($key && $row['date_mail']) $c['dates'][$key] = $row['date_mail'];
            }
            echo json_encode(['ok'=>true,'contacted'=>$c]);
        } catch(Throwable $e) { echo json_encode(['ok'=>true,'contacted'=>[]]); }
        exit;
    }

    $nom=$_POST['nom']??''; $email=$_POST['email']??''; $site=$_POST['site']??''; $notes=$_POST['notes']??'';
    try {
        try{$pdo->exec("ALTER TABLE prospects ADD COLUMN site_url VARCHAR(500) DEFAULT NULL");}catch(Throwable $e){}
        try{$pdo->exec("ALTER TABLE prospects ADD COLUMN mail_envoye TINYINT(1) NOT NULL DEFAULT 0");}catch(Throwable $e){}
        try{$pdo->exec("ALTER TABLE prospects ADD COLUMN date_mail DATETIME DEFAULT NULL");}catch(Throwable $e){}
        $check=$pdo->prepare("SELECT id FROM prospects WHERE email=? OR (nom=? AND site_url=?) LIMIT 1");
        $check->execute([$email?:null,$nom,$site?:null]);
        $existing=$check->fetchColumn();
        if ($existing) {
            $pdo->prepare("UPDATE prospects SET mail_envoye=1,date_mail=NOW(),statut='contacté' WHERE id=?")->execute([$existing]);
            echo json_encode(['ok'=>true,'action'=>'updated']);
        } else {
            $pdo->prepare("INSERT INTO prospects (nom,email,site_url,source,statut,mail_envoye,date_mail,notes) VALUES(?,?,?,'Sazulis IA','contacté',1,NOW(),?)")
                ->execute([$nom,$email?:null,$site?:null,$notes?:null]);
            echo json_encode(['ok'=>true,'action'=>'created']);
        }
    } catch(Throwable $e) { echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
    exit;
}

/* ══════════════════════════════════════
    blacklist_email
    ══════════════════════════════════════ */
if ($action === 'blacklist_email') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        echo json_encode(['ok'=>false, 'error'=>'Email manquant']);
        exit;
    }
    $cleaned = cleanEmail($email);
    $ok = addToBlacklist($pdo, $cleaned, 'manuel');
    echo json_encode(['ok'=>$ok, 'email'=>$cleaned]);
    exit;
}

/* ══════════════════════════════════════════════════════════
    scraper_auto – STRATÉGIE DE RECHERCHE RAPIDE ET SÉCURISÉE
    ══════════════════════════════════════════════════════════ */
if ($action === 'scraper_auto') {
    $secteur = trim($_POST['secteur'] ?? '');
    $ville   = trim($_POST['ville'] ?? '');
    $limit   = min(100, max(5, (int)($_POST['limit'] ?? 20)));

    // Si pas de ville ou si "france", on pioche une grande ville pour garantir une réponse ultra-rapide de l'API
    $grandesVilles = [
        'Paris', 'Marseille', 'Lyon', 'Toulouse', 'Nice', 'Nantes', 'Montpellier', 'Strasbourg',
        'Bordeaux', 'Lille', 'Rennes', 'Reims', 'Le Havre', 'Saint-Étienne', 'Toulon',
        'Grenoble', 'Dijon', 'Angers', 'Nîmes', 'Villeurbanne', 'Aix-en-Provence', 'Clermont-Ferrand',
        'Le Mans', 'Brest', 'Tours', 'Amiens', 'Limoges', 'Perpignan', 'Metz', 'Besançon'
    ];
    
    if (empty($ville) || strtolower($ville) === 'france') {
        $ville = $grandesVilles[array_rand($grandesVilles)];
    }

    $prospects = [];

    // Géocodage de la ville sélectionnée
    $geoR = doGet('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' . urlencode($ville), 5);
    $geo = ($geoR['code'] === 200) ? (json_decode($geoR['body'], true)[0] ?? []) : [];
    $lat = (float)($geo['lat'] ?? 0);
    $lon = (float)($geo['lon'] ?? 0);
    
    if ($lat == 0 || $lon == 0) {
        echo json_encode(['error' => 'Erreur de géolocalisation pour la zone : ' . $ville]);
        exit;
    }

    // Rayon large de 15km autour de la ville choisie pour ramasser un maximum de sites d'un coup
    $rayon = 15000; 
    $query = '[out:json][timeout:25];(';
    if ($secteur && $secteur !== 'commerce') {
        $escapedSecteur = preg_quote($secteur, '/');
        $query .= 'node["shop"~"' . $escapedSecteur . '",i](around:' . $rayon . ',' . $lat . ',' . $lon . ');';
        $query .= 'node["office"~"' . $escapedSecteur . '",i](around:' . $rayon . ',' . $lat . ',' . $lon . ');';
        $query .= 'node["craft"~"' . $escapedSecteur . '",i](around:' . $rayon . ',' . $lat . ',' . $lon . ');';
        $query .= 'node["amenity"~"' . $escapedSecteur . '",i](around:' . $rayon . ',' . $lat . ',' . $lon . ');';
    } else {
        $query .= 'node["shop"](around:' . $rayon . ',' . $lat . ',' . $lon . ');';
        $query .= 'node["office"](around:' . $rayon . ',' . $lat . ',' . $lon . ');';
        $query .= 'node["craft"](around:' . $rayon . ',' . $lat . ',' . $lon . ');';
    }
    $query .= ');out body 350;';

    $osmR = null;
    for ($attempt = 0; $attempt < 2; $attempt++) {
        $osmR = doPost('https://overpass-api.de/api/interpreter', 
            ['Content-Type: application/x-www-form-urlencoded', 'User-Agent: SazulisDashboard/1.0'],
            'data=' . urlencode($query), 25);
        if ($osmR['code'] === 200 && !empty($osmR['body'])) break;
        usleep(500000);
    }

    if (!$osmR || $osmR['code'] !== 200 || empty($osmR['body'])) {
        echo json_encode(['error' => 'Le service cartographique OSM est temporairement surchargé. Relance la recherche.']);
        exit;
    }

    $elements = json_decode($osmR['body'], true)['elements'] ?? [];
    if (empty($elements)) {
        echo json_encode(['error' => 'Aucune entreprise trouvée à ' . $ville . '. Tente un autre secteur.']);
        exit;
    }

    shuffle($elements);
    
    // On s'autorise à scanner jusqu'à 200 sites web à la volée
    $maxHttpRequests = 200; 
    $httpRequestsCount = 0;

    foreach ($elements as $el) {
        if (count($prospects) >= $limit) break;
        if ($httpRequestsCount >= $maxHttpRequests) break; 
        
        $tags = $el['tags'] ?? [];
        $nom = $tags['name'] ?? ($tags['brand'] ?? '');
        if (strlen($nom) < 3) continue;

        $site = $tags['website'] ?? $tags['contact:website'] ?? '';
        $email = $tags['email'] ?? $tags['contact:email'] ?? '';
        $phone = $tags['phone'] ?? $tags['contact:phone'] ?? '';

        if (empty($site) || !filter_var($site, FILTER_VALIDATE_URL)) {
            continue;
        }

        // --- CRAWLING ULTRA RAPIDE (2 secondes max de patience par site) ---
        $httpRequestsCount++;
        $pageData = doGet($site, 2); 
        if ($pageData['code'] !== 200 || empty($pageData['body'])) {
            continue; 
        }

        $html = $pageData['body'];
        $audit = evaluerVetusteSite($html, $site);

        if ($audit['critique'] === false) {
            continue; 
        }

        // Extraction de l'email si absent d'OSM
        if (empty($email)) {
            if (preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i', $html, $matches)) {
                $foundEmails = array_unique($matches[0]);
                foreach ($foundEmails as $fe) {
                    if (!preg_match('/\.png|\.jpg|\.jpeg|\.gif|\.svg|wix|wordpress|sentry|bootstrap/i', $fe)) {
                        $email = $fe;
                        break;
                    }
                }
            }
        }

        if (!empty($email)) {
            $lowerEmail = strtolower($email);
            if (str_replace(['postmaster', 'abuse', 'webmaster', 'noreply', 'example'], '', $lowerEmail) !== $lowerEmail) {
                $email = ''; 
            }
        }

        if (!empty($email) && isEmailBlacklisted($pdo, $email)) {
            continue; 
        }

        // Identification de la plateforme
        $plateforme = 'Code Fixe / Custom';
        $host = parse_url($site, PHP_URL_HOST);
        if (strpos($host, 'wix') !== false) $plateforme = 'Wix';
        elseif (strpos($host, 'jimdo') !== false) $plateforme = 'Jimdo';
        elseif (strpos($host, 'webnode') !== false) $plateforme = 'Webnode';
        elseif (strpos($html, 'wp-content') !== false || strpos($site, 'wp-') !== false) $plateforme = 'WordPress';

        $prospectVille = $tags['addr:city'] ?? $ville;
        $anomaliesList = implode(', ', $audit['anomalies']);
        $approche = "Bonjour, j'ai analysé votre site internet et j'ai relevé des optimisations prioritaires pour vos clients mobiles et votre visibilité Google : " . strtolower($anomaliesList) . ". Discutons-en ensemble.";

        $prospects[] = [
            'nom'           => $nom,
            'url'           => $site,
            'emails'        => !empty($email) ? [$email] : [],
            'plateforme'    => $plateforme,
            'pourquoi'      => "Défauts techniques : " . $anomaliesList . " (Score: " . $audit['score'] . "/100)",
            'score'         => $audit['score'],
            'reel'          => true,
            'besoinRefonte' => true,
            'approche'      => $approche,
            'secteur'       => $secteur ?: 'commerce',
            'zone'          => $prospectVille
        ];
    }

    if (empty($prospects)) {
        echo json_encode(['error' => 'Aucun site à optimiser trouvé lors de cette passe. Relance la recherche pour changer de ville automatiquement !']);
    } else {
        echo json_encode(['ok' => true, 'results' => $prospects]);
    }
    exit;
}

/* ── Scraper manuel ── */
if ($action === 'scraper_manual') {
    $urlsText = trim($_POST['urls'] ?? '');
    if (!$urlsText) { echo json_encode(['error' => 'Aucune URL fournie']); exit; }

    $urls = array_filter(array_map('trim', explode("\n", $urlsText)), function($u) {
        return filter_var($u, FILTER_VALIDATE_URL);
    });
    if (empty($urls)) { echo json_encode(['error' => 'Aucune URL valide']); exit; }

    $results = [];
    foreach ($urls as $url) {
        $page = doGet($url, 10);
        if ($page['code'] !== 200 || empty($page['body'])) {
            $results[] = ['url' => $url, 'error' => 'HTTP ' . $page['code']];
            continue;
        }

        $html = $page['body'];
        $plateforme = 'Inconnu';
        if (strpos($html, 'wp-content') !== false) $plateforme = 'WordPress';
        elseif (strpos($html, 'wix.com') !== false) $plateforme = 'Wix';

        preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $html, $matches);
        $emails = array_unique(array_filter($matches[0], function($e) {
            return !preg_match('/example|yourname|domain\.com|\.png|\.jpg|\.css|\.js/i', $e);
        }));

        $results[] = [
            'url'        => $url,
            'plateforme' => $plateforme,
            'emails'     => array_values($emails),
            'nom'        => parse_url($url, PHP_URL_HOST),
        ];
    }
    echo json_encode(['ok' => true, 'results' => $results]);
    exit;
}

echo json_encode(['ok'=>false,'error'=>'Action inconnue: '.$action]);