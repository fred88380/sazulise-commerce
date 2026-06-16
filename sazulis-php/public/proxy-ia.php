<?php
/**
 * proxy-ia.php — Point d'entrée AJAX pour le dashboard admin Sazulis
 * Actions : ia_proxy, scraper_auto, scraper_manual, enrich_prospect,
 *           marquer_mail_envoye, get_contacted, blacklist_email
 */

declare(strict_types=1);

ini_set('display_errors', '0');
@set_time_limit(300);
@ini_set('memory_limit', '512M');
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
while (@ob_get_level()) { @ob_end_clean(); }

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, no-store');
}

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (@ob_get_level()) { @ob_end_clean(); }
        echo json_encode(['error' => 'PHP Fatal: ' . $err['message'] . ' ligne ' . $err['line']]);
    }
});

require_once dirname(__DIR__) . '/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$currentUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
if (!$currentUser || (($currentUser['role'] ?? '') !== 'admin')) {
    echo json_encode(['error' => 'Accès refusé']); exit;
}

use App\Core\Database;
$pdo = Database::getConnection();
if ($pdo === null) { echo json_encode(['error' => 'DB indisponible']); exit; }

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_blacklist (
        id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL,
        motif VARCHAR(100) DEFAULT 'manuel',
        date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS prospects (
        id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL, site_url VARCHAR(500) DEFAULT NULL,
        source VARCHAR(100) DEFAULT 'Sazulis IA', statut VARCHAR(50) DEFAULT 'nouveau',
        notes TEXT DEFAULT NULL, mail_envoye TINYINT(1) NOT NULL DEFAULT 0,
        date_mail DATETIME DEFAULT NULL,
        date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Throwable) {}

function sz_get(string $url, int $timeout = 4): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>$timeout,
        CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_FOLLOWLOCATION=>true, CURLOPT_MAXREDIRS=>3,
        CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>false,
        CURLOPT_HTTPHEADER=>['User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Accept: text/html,application/xhtml+xml;q=0.9,*/*;q=0.8']]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return ['code'=>(int)$code,'body'=>(string)($body?:''),'error'=>$err];
}

function sz_post(string $url, array $headers, string $payload, int $timeout = 25): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload,
        CURLOPT_TIMEOUT=>$timeout, CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_SSL_VERIFYPEER=>false, CURLOPT_SSL_VERIFYHOST=>false, CURLOPT_HTTPHEADER=>$headers]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return ['code'=>(int)$code,'body'=>(string)($body?:''),'error'=>$err];
}

function sz_read_env_key(): string {
    $dir = dirname(__DIR__);
    for ($i = 0; $i < 3; $i++) {
        $f = $dir . '/.env';
        if (is_file($f)) {
            foreach (file($f, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $line) {
                $line = trim($line);
                if (str_starts_with($line, 'CLE-API=')) return trim(substr($line, 8));
            }
        }
        $parent = dirname($dir); if ($parent === $dir) break; $dir = $parent;
    }
    return '';
}

function sz_detect_provider(string $key): string {
    if (str_starts_with($key, 'AIza')) return 'gemini';
    if (str_starts_with($key, 'gsk_')) return 'groq';
    if (str_starts_with($key, 'sk-or-')) return 'openrouter';
    if (str_starts_with($key, 'sk-ant')) return 'anthropic';
    return 'openrouter';
}

function sz_audit_site(string $html, string $url): array {
    $score = 0; $anomalies = [];
    if (stripos($html,'name="viewport"')===false && stripos($html,"name='viewport'")===false) { $score+=35; $anomalies[]='Non adapté mobiles (viewport manquant)'; }
    if (strpos($url,'https://')===false && substr_count($html,'https://')<3) { $score+=20; $anomalies[]='Sécurité obsolète (HTTP)'; }
    if (preg_match('/©\s*(200[0-9]|201[0-8])/',$html,$m)) { $score+=20; $anomalies[]='Copyright obsolète ('.$m[1].')'; }
    if (stripos($html,'<frameset>')!==false) { $score+=25; $anomalies[]='Utilisation de frames (années 2000)'; }
    elseif (substr_count($html,'<table')>12 && substr_count($html,'<div')<20) { $score+=20; $anomalies[]='Mise en page archaïque par tableaux'; }
    if (stripos($html,'rel="canonical"')===false && stripos($html,"rel='canonical'")===false) { $score+=15; $anomalies[]='Balise canonique absente'; }
    if (stripos($html,'property="og:title"')===false) { $score+=15; $anomalies[]='Balises Open Graph manquantes'; }
    if (stripos($html,'.swf')!==false) { $score+=15; $anomalies[]='Flash détecté (.swf)'; }
    if (preg_match('/jquery[-.]1\.[0-9]/i',$html)) { $score+=15; $anomalies[]='jQuery v1.x obsolète'; }
    if (stripos($html,'next/script')!==false || stripos($html,'_nuxt/')!==false || stripos($html,'wp-content')!==false) { $score=max(0,$score-40); }
    $score=min(100,$score);
    return ['score'=>$score,'anomalies'=>$anomalies,'critique'=>($score>=20),'priorite'=>$score>=50?'CRITIQUE':'Moyenne'];
}

function sz_extract_emails(string $html): array {
    preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i',$html,$matches);
    $clean=[];
    foreach (array_unique($matches[0]??[]) as $e) {
        if (!preg_match('/\.(png|jpg|jpeg|gif|svg|css|js)$/i',$e) && !preg_match('/sentry|wix|abuse|noreply|postmaster|example/i',$e)) $clean[]=$e;
    }
    return $clean;
}

function sz_is_blacklisted(\PDO $pdo, string $email): bool {
    $stmt=$pdo->prepare('SELECT 1 FROM email_blacklist WHERE email=?'); $stmt->execute([strtolower(trim($email))]);
    return (bool)$stmt->fetchColumn();
}

$action = trim((string)($_POST['action']??''));

if ($action==='ia_proxy') {
    $prompt=trim((string)($_POST['prompt']??'')); $provider=trim((string)($_POST['provider']??''));
    if (!$prompt) { echo json_encode(['error'=>'Prompt vide']); exit; }
    $key=sz_read_env_key(); if (!$key) { echo json_encode(['error'=>'Clé CLE-API introuvable dans .env']); exit; }
    if (!$provider) $provider=sz_detect_provider($key);
    $jsonH=['Content-Type: application/json'];
    if ($provider==='gemini') {
        $r=sz_post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$key}",$jsonH,json_encode(['contents'=>[['parts'=>[['text'=>$prompt]]]],'generationConfig'=>['maxOutputTokens'=>1500,'temperature'=>0.9]]));
        if ($r['code']!==200){$d=json_decode($r['body'],true);echo json_encode(['error'=>$d['error']['message']??"HTTP {$r['code']}"]);exit;}
        $d=json_decode($r['body'],true); echo json_encode(['content'=>[['type'=>'text','text'=>$d['candidates'][0]['content']['parts'][0]['text']??'']]]); exit;
    }
    if ($provider==='groq') {
        $r=sz_post('https://api.groq.com/openai/v1/chat/completions',array_merge($jsonH,['Authorization: Bearer '.$key]),json_encode(['model'=>'llama-3.3-70b-versatile','max_tokens'=>1500,'temperature'=>0.9,'messages'=>[['role'=>'user','content'=>$prompt]]]));
        if ($r['code']!==200){$d=json_decode($r['body'],true);echo json_encode(['error'=>$d['error']['message']??"HTTP {$r['code']}"]);exit;}
        $d=json_decode($r['body'],true); echo json_encode(['content'=>[['type'=>'text','text'=>$d['choices'][0]['message']['content']??'']]]); exit;
    }
    if ($provider==='anthropic') {
        $r=sz_post('https://api.anthropic.com/v1/messages',array_merge($jsonH,['x-api-key: '.$key,'anthropic-version: 2023-06-01']),json_encode(['model'=>'claude-haiku-4-5-20251001','max_tokens'=>1500,'temperature'=>0.9,'messages'=>[['role'=>'user','content'=>$prompt]]]));
        if ($r['code']!==200){$d=json_decode($r['body'],true);echo json_encode(['error'=>$d['error']['message']??"HTTP {$r['code']}"]);exit;} echo $r['body']; exit;
    }
    $url='https://openrouter.ai/api/v1/chat/completions'; $orH=array_merge($jsonH,['Authorization: Bearer '.$key,'HTTP-Referer: '.($_SERVER['HTTP_HOST']??'localhost')]);
    foreach (['meta-llama/llama-3.3-70b-instruct:free','qwen/qwen3-8b:free','mistralai/mistral-small-3.1-24b-instruct:free'] as $model) {
        $r=sz_post($url,$orH,json_encode(['model'=>$model,'max_tokens'=>1500,'temperature'=>0.9,'messages'=>[['role'=>'user','content'=>$prompt]]]));
        if ($r['code']===200){$d=json_decode($r['body'],true);$t=$d['choices'][0]['message']['content']??'';if($t){echo json_encode(['content'=>[['type'=>'text','text'=>$t]]]);exit;}}
    }
    echo json_encode(['error'=>'Tous les modèles IA indisponibles.']); exit;
}

if ($action==='scraper_auto') {
    $secteur=trim((string)($_POST['secteur']??'')); $ville=trim((string)($_POST['ville']??'')); $limit=min(50,max(5,(int)($_POST['limit']??20)));
    $grandesVilles=['Paris','Marseille','Lyon','Toulouse','Nice','Nantes','Bordeaux','Lille','Rennes','Strasbourg','Grenoble'];
    if (empty($ville)||strtolower($ville)==='france') $ville=$grandesVilles[array_rand($grandesVilles)];
    $geoR=sz_get('https://nominatim.openstreetmap.org/search?format=json&limit=1&q='.urlencode($ville),5);
    $geo=($geoR['code']===200)?(json_decode($geoR['body'],true)[0]??[]):[];
    $lat=(float)($geo['lat']??0); $lon=(float)($geo['lon']??0);
    if ($lat===0.0&&$lon===0.0){echo json_encode(['error'=>'Géolocalisation impossible pour : '.$ville]);exit;}
    $rayon=15000; $q='[out:json][timeout:25];(';
    foreach(['shop','office','craft','amenity'] as $f) {
        $q.=$secteur?'node["'.$f.'"~"'.preg_quote($secteur,'/').'",i](around:'.$rayon.','.$lat.','.$lon.');':'node["'.$f.'"](around:'.$rayon.','.$lat.','.$lon.');';
    }
    $q.=');out body 350;';
    $osmR=sz_post('https://overpass-api.de/api/interpreter',['Content-Type: application/x-www-form-urlencoded','User-Agent: SazulisDashboard/1.0'],'data='.urlencode($q),25);
    if ($osmR['code']!==200||empty($osmR['body'])){echo json_encode(['error'=>'Service OSM surchargé, réessaie.']);exit;}
    $elements=json_decode($osmR['body'],true)['elements']??[];
    if (empty($elements)){echo json_encode(['error'=>'Aucune entreprise trouvée à '.$ville]);exit;}
    shuffle($elements); $prospects=[]; $httpCount=0; $maxHttp=150;
    foreach ($elements as $el) {
        if (count($prospects)>=$limit||$httpCount>=$maxHttp) break;
        $tags=$el['tags']??[]; $nom=(string)($tags['name']??$tags['brand']??''); if (strlen($nom)<3) continue;
        $site=(string)($tags['website']??$tags['contact:website']??''); $email=(string)($tags['email']??$tags['contact:email']??'');
        if (!filter_var($site,FILTER_VALIDATE_URL)) continue;
        $httpCount++; $page=sz_get($site,2);
        if ($page['code']!==200||strlen($page['body'])<500) continue;
        $audit=sz_audit_site($page['body'],$site); if (!$audit['critique']) continue;
        if (empty($email)){$found=sz_extract_emails($page['body']);$email=$found[0]??'';}
        if ($email&&sz_is_blacklisted($pdo,$email)) continue;
        $host=(string)parse_url($site,PHP_URL_HOST); $plateforme='Code fixe / Custom';
        if (str_contains($host,'wix')) $plateforme='Wix'; elseif (str_contains($page['body'],'wp-content')) $plateforme='WordPress';
        $anomaliesList=implode(', ',$audit['anomalies']);
        $prospects[]=['nom'=>$nom,'url'=>$site,'emails'=>$email?[$email]:[],'plateforme'=>$plateforme,
            'pourquoi'=>'Défauts : '.$anomaliesList.' (Score: '.$audit['score'].'/100)',
            'score'=>$audit['score'],'approche'=>'Bonjour, j\'ai analysé votre site : '.strtolower($anomaliesList).'. Discutons.',
            'secteur'=>$secteur?:'commerce','zone'=>(string)($tags['addr:city']??$ville)];
    }
    if (empty($prospects)) echo json_encode(['error'=>'Aucun site obsolète trouvé. Relance pour changer de zone.']);
    else echo json_encode(['ok'=>true,'results'=>$prospects]);
    exit;
}

if ($action==='scraper_manual') {
    $urlsText=trim((string)($_POST['urls']??'')); if (!$urlsText){echo json_encode(['error'=>'Aucune URL']);exit;}
    $urls=array_filter(array_map('trim',explode("\n",$urlsText)),fn($u)=>filter_var($u,FILTER_VALIDATE_URL)!==false);
    if (empty($urls)){echo json_encode(['error'=>'Aucune URL valide']);exit;}
    $results=[];
    foreach ($urls as $url) {
        $page=sz_get($url,10); if ($page['code']!==200||empty($page['body'])){$results[]=['url'=>$url,'error'=>'HTTP '.$page['code']];continue;}
        $audit=sz_audit_site($page['body'],$url); $emails=sz_extract_emails($page['body']);
        $results[]=['url'=>$url,'score'=>$audit['score'],'anomalies'=>$audit['anomalies'],'emails'=>$emails];
    }
    echo json_encode(['ok'=>true,'results'=>$results]); exit;
}

if ($action==='enrich_prospect') {
    $nom=trim((string)($_POST['nom']??'')); $zone=trim((string)($_POST['zone']??''));
    if (!$nom){echo json_encode(['ok'=>false,'error'=>'Nom manquant']);exit;}
    $geoR=sz_get('https://nominatim.openstreetmap.org/search?format=json&limit=1&q='.urlencode($zone?:'France'),5);
    $geo=($geoR['code']===200)?(json_decode($geoR['body'],true)[0]??[]):[];
    $lat=(float)($geo['lat']??46.603354); $lon=(float)($geo['lon']??1.888334);
    $n=preg_replace('/[^\w\s]/u','',$nom);
    $r=sz_post('https://overpass-api.de/api/interpreter',['Content-Type: application/x-www-form-urlencoded','User-Agent: SazulisDashboard/1.0'],
        'data='.urlencode('[out:json][timeout:8];(node["name"~"'.$n.'",i](around:15000,'.$lat.','.$lon.'););out body 3;'),10);
    $site=$email=$phone='';
    if ($r['code']===200){foreach(json_decode($r['body'],true)['elements']??[] as $el){$t=$el['tags']??[];$site=(string)($t['website']??$t['contact:website']??'');$email=(string)($t['email']??$t['contact:email']??'');$phone=(string)($t['phone']??$t['contact:phone']??'');if($site||$email)break;}}
    echo json_encode(['ok'=>true,'site'=>$site?:null,'email'=>$email?:null,'phone'=>$phone?:null]); exit;
}

if ($action==='marquer_mail_envoye') {
    $nom=(string)($_POST['nom']??''); $email=(string)($_POST['email']??''); $site=(string)($_POST['site']??''); $notes=(string)($_POST['notes']??'');
    try {
        $check=$pdo->prepare('SELECT id FROM prospects WHERE email=? OR (nom=? AND site_url=?) LIMIT 1'); $check->execute([$email?:null,$nom,$site?:null]); $existing=$check->fetchColumn();
        if ($existing){$pdo->prepare("UPDATE prospects SET mail_envoye=1,date_mail=NOW(),statut='contacté' WHERE id=?")->execute([$existing]);echo json_encode(['ok'=>true,'action'=>'updated']);}
        else{$pdo->prepare("INSERT INTO prospects (nom,email,site_url,source,statut,mail_envoye,date_mail,notes) VALUES(?,?,?,'Sazulis IA','contacté',1,NOW(),?)")->execute([$nom,$email?:null,$site?:null,$notes?:null]);echo json_encode(['ok'=>true,'action'=>'created']);}
    } catch(\Throwable $e){echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}
    exit;
}

if ($action==='get_contacted') {
    try {
        $rows=$pdo->query("SELECT nom,email,site_url,date_mail FROM prospects WHERE mail_envoye=1 AND date_mail>=DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchAll();
        $pdo->exec("UPDATE prospects SET mail_envoye=0,date_mail=NULL WHERE mail_envoye=1 AND date_mail<DATE_SUB(NOW(),INTERVAL 7 DAY)");
        $c=['email'=>[],'nom'=>[],'site'=>[],'dates'=>[]];
        foreach ($rows as $row){if($row['email'])$c['email'][]=strtolower(trim($row['email']));if($row['nom'])$c['nom'][]=strtolower(trim($row['nom']));if($row['site_url'])$c['site'][]=strtolower(trim($row['site_url']));$key=strtolower(trim($row['email']?:$row['nom']));if($key&&$row['date_mail'])$c['dates'][$key]=$row['date_mail'];}
        echo json_encode(['ok'=>true,'contacted'=>$c]);
    } catch(\Throwable){echo json_encode(['ok'=>true,'contacted'=>[]]);}
    exit;
}

if ($action==='blacklist_email') {
    $email=strtolower(trim((string)($_POST['email']??'')));
    if (!$email){echo json_encode(['ok'=>false,'error'=>'Email manquant']);exit;}
    try{$pdo->prepare('INSERT IGNORE INTO email_blacklist (email,motif) VALUES(?,?)')->execute([$email,'manuel']);echo json_encode(['ok'=>true,'email'=>$email]);}
    catch(\Throwable $e){echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}
    exit;
}

echo json_encode(['error'=>'Action inconnue : '.$action]);
