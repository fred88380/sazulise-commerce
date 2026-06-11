<?php
require_once __DIR__ . '/../protect.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── CSRF ─────────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Sécurité : validation URL (anti-SSRF) ────────────────────────────────────
function isUrlSafe(string $url, string $allowedDomain = ''): bool {
    $parsed = parse_url($url);
    if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) return false;
    $host = $parsed['host'] ?? '';
    if (empty($host)) return false;
    if (!empty($allowedDomain)) {
        $norm = fn($h) => strtolower(preg_replace('/^www\./i', '', $h));
        if ($norm($host) === $norm($allowedDomain)) return true;
    }
    $ip = gethostbyname($host);
    if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) return false;
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function safeFetch(string $url, int $timeout = 15, string $allowedDomain = '') {    if (!isUrlSafe($url, $allowedDomain)) return false;
    $ctx = stream_context_create([
        'http' => ['timeout' => $timeout, 'follow_location' => 1, 'max_redirects' => 5, 'user_agent' => 'Mozilla/5.0 (AuditBot)'],
        'ssl'  => ['verify_peer' => true]
    ]);
    return @file_get_contents($url, false, $ctx);
}

function safeGetHeaders(string $url, int $timeout = 10, string $allowedDomain = '') {    if (!isUrlSafe($url, $allowedDomain)) return false;
    $ctx = stream_context_create(['http' => ['timeout' => $timeout, 'follow_location' => 1, 'max_redirects' => 5, 'method' => 'HEAD', 'user_agent' => 'Mozilla/5.0 (AuditBot)']]);
    return @get_headers($url, 1, $ctx);
}

// ─── Audit d'une page ─────────────────────────────────────────────────────────
function auditPage(string $url, string $allowedDomain = ''): array {
    $r = [
        'url' => $url, 'https' => stripos($url, 'https://') === 0,
        'response_time' => null, 'status_code' => null, 'page_size' => null,
        'server' => null, 'headers' => [], 'favicon' => false, 'robots' => false,
        'sitemap' => false, 'title' => null, 'title_len' => 0, 'description' => null,
        'desc_len' => 0, 'keywords' => null, 'canonical' => null, 'og_title' => null,
        'og_desc' => null, 'h1' => [], 'h2' => [], 'h3' => [], 'images' => [],
        'images_no_alt' => 0, 'links_broken' => [], 'links_total' => 0,
        'scripts_ext' => 0, 'technologies' => [], 'mobile_viewport' => false,
        'charset' => null, 'lang' => null,
        'seo_score' => 0, 'perf_score' => 0, 'sec_score' => 0, 'error' => null,
    ];

    $secHeaders = ['Strict-Transport-Security','Content-Security-Policy','X-Frame-Options','X-Content-Type-Options','Referrer-Policy','Permissions-Policy'];

    $t0 = microtime(true);
    $headers = safeGetHeaders($url, 10, $allowedDomain);
    $r['response_time'] = round((microtime(true) - $t0) * 1000);

    if ($headers && is_array($headers)) {
        $statusLine = is_array($headers[0]) ? end($headers[0]) : $headers[0];
        preg_match('/HTTP\/[\d.]+ (\d+)/', $statusLine, $sm);
        $r['status_code'] = isset($sm[1]) ? (int)$sm[1] : null;
        $r['server'] = $headers['Server'] ?? null;
        foreach ($secHeaders as $h) $r['headers'][$h] = $headers[$h] ?? null;
    } else {
        foreach ($secHeaders as $h) $r['headers'][$h] = null;
    }

    $html = safeFetch($url, 15, $allowedDomain);
    if ($html === false) { $r['error'] = "Impossible de récupérer la page."; return $r; }

    $r['page_size'] = strlen($html);
    $base = rtrim(parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST), '/');

    $r['favicon'] = (bool)preg_match('/rel=["\'](?:shortcut icon|icon)["\'][^>]*>/i', $html);
    $robots = safeFetch($base . '/robots.txt', 5, $allowedDomain);
    $r['robots'] = ($robots !== false && strlen($robots) > 10);
    foreach ([$base . '/sitemap-index.xml', $base . '/sitemap.xml'] as $sm) {
        $s = safeFetch($sm, 5, $allowedDomain);
        if ($s !== false && strlen($s) > 10) { $r['sitemap'] = true; break; }
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $htmlEl = $dom->getElementsByTagName('html')->item(0);
    $r['lang'] = $htmlEl ? $htmlEl->getAttribute('lang') : null;
    foreach ($xpath->query('//meta[@charset]') as $node) { $r['charset'] = $node->getAttribute('charset'); break; }

    $titleNode = $xpath->query('//title')->item(0);
    if ($titleNode) { $r['title'] = trim($titleNode->textContent); $r['title_len'] = mb_strlen($r['title']); }

    foreach ($xpath->query('//meta[@name="viewport"]') as $node) { $r['mobile_viewport'] = true; break; }
    foreach ($xpath->query('//meta[@name]') as $node) {
        $name = strtolower($node->getAttribute('name')); $content = $node->getAttribute('content');
        if ($name === 'description') { $r['description'] = $content; $r['desc_len'] = mb_strlen($content); }
        if ($name === 'keywords')    $r['keywords'] = $content;
    }
    foreach ($xpath->query('//link[@rel="canonical"]') as $node) { $r['canonical'] = $node->getAttribute('href'); break; }
    foreach ($xpath->query('//meta[@property]') as $node) {
        $prop = strtolower($node->getAttribute('property'));
        if ($prop === 'og:title')       $r['og_title'] = $node->getAttribute('content');
        if ($prop === 'og:description') $r['og_desc']  = $node->getAttribute('content');
    }

    foreach (['h1','h2','h3'] as $tag) {
        foreach ($xpath->query('//' . $tag) as $node) $r[$tag][] = trim($node->textContent);
    }

    foreach ($xpath->query('//img') as $img) {
        $alt = $img->getAttribute('alt');
        $r['images'][] = ['src' => $img->getAttribute('src'), 'alt' => $alt, 'has_alt' => $alt !== ''];
        if ($alt === '') $r['images_no_alt']++;
    }

    foreach ($xpath->query('//script[@src]') as $node) {
        if (strpos($node->getAttribute('src'), 'http') === 0) $r['scripts_ext']++;
    }

    $linksChecked = 0;
    foreach ($xpath->query('//a[@href]') as $node) {
        $href = $node->getAttribute('href');
        if (empty($href) || $href[0] === '#' || strpos($href,'mailto:') === 0 || strpos($href,'tel:') === 0) continue;
        $r['links_total']++;
        if ($linksChecked >= 15) continue;
        $fullUrl = strpos($href,'http') === 0 ? $href : $base . '/' . ltrim($href, '/');
        if (!isUrlSafe($fullUrl, $allowedDomain)) continue;
        $lh = safeGetHeaders($fullUrl, 5, $allowedDomain);
        if ($lh) {
            $ls = is_array($lh[0]) ? end($lh[0]) : $lh[0];
            preg_match('/HTTP\/[\d.]+ (\d+)/', $ls, $lm);
            $code = isset($lm[1]) ? (int)$lm[1] : 0;
            if ($code >= 400) $r['links_broken'][] = ['url' => $fullUrl, 'code' => $code];
        }
        $linksChecked++;
    }

    $tech = [];
    if (preg_match('/wp-content|wp-includes/i', $html))              $tech[] = 'WordPress';
    if (preg_match('/Joomla/i', $html))                               $tech[] = 'Joomla';
    if (preg_match('/Drupal/i', $html))                               $tech[] = 'Drupal';
    if (preg_match('/shopify/i', $html))                              $tech[] = 'Shopify';
    if (preg_match('/PrestaShop/i', $html))                           $tech[] = 'PrestaShop';
    if (preg_match('/next\.js|__NEXT_DATA__|_next\//i', $html))      $tech[] = 'Next.js';
    if (preg_match('/nuxt|__nuxt/i', $html))                          $tech[] = 'Nuxt.js';
    if (preg_match('/react|ReactDOM/i', $html))                       $tech[] = 'React';
    if (preg_match('/vue\.js|Vue\.config/i', $html))                  $tech[] = 'Vue.js';
    if (preg_match('/angular\.js|ng-version/i', $html))               $tech[] = 'Angular';
    if (preg_match('/jquery/i', $html))                               $tech[] = 'jQuery';
    if (preg_match('/bootstrap/i', $html))                            $tech[] = 'Bootstrap';
    if (preg_match('/tailwindcss|tailwind/i', $html))                 $tech[] = 'Tailwind CSS';
    if (preg_match('/gtag|google-analytics|googletagmanager/i', $html)) $tech[] = 'Google Analytics';
    if (preg_match('/cookiebot|tarteaucitron|axeptio/i', $html))      $tech[] = 'Gestion cookies';
    if (isset($r['server'])) {
        if (preg_match('/apache/i', $r['server']))    $tech[] = 'Apache';
        if (preg_match('/nginx/i',  $r['server']))    $tech[] = 'Nginx';
        if (preg_match('/litespeed/i', $r['server'])) $tech[] = 'LiteSpeed';
    }
    $r['technologies'] = array_unique($tech);

    // Scores
    $seo = 0;
    if ($r['title'] && $r['title_len'] >= 30 && $r['title_len'] <= 65) $seo += 20; elseif ($r['title']) $seo += 10;
    if ($r['description'] && $r['desc_len'] >= 70 && $r['desc_len'] <= 160) $seo += 20; elseif ($r['description']) $seo += 10;
    if (count($r['h1']) === 1) $seo += 15; elseif (count($r['h1']) > 1) $seo += 5;
    if (!empty($r['h2']))            $seo += 10;
    if ($r['favicon'])               $seo += 5;
    if ($r['robots'])                $seo += 5;
    if ($r['sitemap'])               $seo += 5;
    if ($r['canonical'])             $seo += 5;
    if ($r['mobile_viewport'])       $seo += 5;
    if ($r['lang'])                  $seo += 5;
    if ($r['images_no_alt'] === 0 && count($r['images']) > 0) $seo += 5;
    $r['seo_score'] = min(100, $seo);

    $perf = 100;
    if ($r['response_time'] > 3000)     $perf -= 40; elseif ($r['response_time'] > 1500) $perf -= 20; elseif ($r['response_time'] > 800) $perf -= 10;
    if ($r['page_size'] > 1024*1024)    $perf -= 30; elseif ($r['page_size'] > 512*1024) $perf -= 15;
    if ($r['scripts_ext'] > 10)         $perf -= 20; elseif ($r['scripts_ext'] > 5)      $perf -= 10;
    $r['perf_score'] = max(0, $perf);

    $sec = 0;
    if ($r['https']) $sec += 30;
    $sec += min(60, count(array_filter($r['headers'])) * 10);
    if (!empty($r['headers']['Strict-Transport-Security'])) $sec += 10;
    $r['sec_score'] = min(100, $sec);

    return $r;
}

function extractUrlsFromSitemap(string $xml, string $allowedDomain, int $depth = 0): array {
    if ($depth > 2) return [];
    $urls = [];
    if (stripos($xml, '<sitemapindex') !== false) {
        if (preg_match_all('/<loc>([^<]+)<\/loc>/i', $xml, $sm)) {
            foreach ($sm[1] as $childUrl) {
                $child = safeFetch(trim($childUrl), 10, $allowedDomain);
                if ($child) $urls = array_merge($urls, extractUrlsFromSitemap($child, $allowedDomain, $depth + 1));
            }
        }
    } else {
        $clean = preg_replace('/<image:[^>]*>.*?<\/image:[^>]*>/is', '', $xml);
        if (preg_match_all('/<loc>([^<]+)<\/loc>/i', $clean, $lm)) $urls = array_map('trim', $lm[1]);
    }
    return $urls;
}

// ─── Traitement formulaire ────────────────────────────────────────────────────
$rapport = null; $error = null; $globalResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Requête invalide (CSRF).";
    } else {
        $rawSite = trim($_POST['site'] ?? '');
        if (empty($rawSite)) {
            $error = "Veuillez saisir une URL.";
        } else {
            if (!preg_match('#^https?://#i', $rawSite)) $rawSite = 'https://' . $rawSite;
            $allowedDomain = parse_url($rawSite, PHP_URL_HOST);
            if (!isUrlSafe($rawSite, $allowedDomain)) {
                $error = "Cette URL n'est pas autorisée.";
            } else {
                $site = $rawSite; $baseRoot = rtrim($site, '/');
                if (!empty($_POST['fullsite'])) {
                    $urls = [];
                    foreach ([$baseRoot.'/sitemap-index.xml', $baseRoot.'/sitemap.xml'] as $c) {
                        $xml = safeFetch($c, 10, $allowedDomain);
                        if ($xml && strlen($xml) > 10) { $urls = extractUrlsFromSitemap($xml, $allowedDomain); break; }
                    }
                    if (empty($urls)) {
                        $homeHtml = safeFetch($site, 15, $allowedDomain);
                        if ($homeHtml) {
                            preg_match_all('/<a\s+[^>]*href=["\']([^"\'#?]+)["\']/i', $homeHtml, $am);
                            foreach ($am[1] ?? [] as $href) $urls[] = strpos($href,'http') === 0 ? $href : $baseRoot.'/'.ltrim($href,'/');
                        }
                    }
                    $urls = array_filter($urls, fn($u) => isUrlSafe($u, $allowedDomain));
                    $urls = array_values(array_unique($urls));
                    $urls = array_slice($urls, 0, 30);
                    if (empty($urls)) $urls = [$site];
                    $globalResults = array_map(fn($u) => auditPage($u, $allowedDomain), $urls);
                } else {
                    $rapport = auditPage($site, $allowedDomain);
                    if ($rapport['error']) { $error = $rapport['error']; $rapport = null; }
                }
            }
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Helpers affichage ───────────────────────────────────────────────────────
function scoreColor(int $s): string { return $s >= 75 ? '#22c55e' : ($s >= 50 ? '#f59e0b' : '#ef4444'); }
function scoreLabel(int $s): string { return $s >= 75 ? 'Bon' : ($s >= 50 ? 'Moyen' : 'Faible'); }
function badge(bool $ok, string $yes = 'Oui ✅', string $no = 'Non ❌'): string {
    return '<span class="badge '.($ok?'ok':'bad').'">'.($ok?$yes:$no).'</span>';
}
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function buildTips(array $r): array {
    $tips = [];
    if (!$r['https'])           $tips[] = ['high','🔒','Activer HTTPS','Un certificat SSL est indispensable pour le SEO et la confiance utilisateur. Google pénalise les sites en HTTP.','Contactez votre hébergeur pour activer Let\'s Encrypt gratuitement.'];
    if (!$r['title'])           $tips[] = ['high','📝','Balise <title> absente','La balise title est le facteur SEO on-page le plus important.','Ajoutez une balise <title> décrivant le contenu de la page en 30–65 caractères.'];
    elseif ($r['title_len']<30) $tips[] = ['medium','📝','Title trop court ('.$r['title_len'].' car.)','Un title trop court est peu informatif pour Google.','Allongez-le à au moins 30 caractères. Actuel : "'.mb_substr($r['title'],0,60).'"'];
    elseif ($r['title_len']>65) $tips[] = ['medium','📝','Title trop long ('.$r['title_len'].' car.)','Il sera tronqué dans les résultats Google (SERP).','Raccourcissez-le à 65 caractères maximum. Actuel : "'.mb_substr($r['title'],0,60).'..."'];
    if (!$r['description'])     $tips[] = ['high','📄','Meta description absente','Elle influence le taux de clic dans Google même si elle ne change pas le ranking.','Ajoutez <meta name="description" content="..."> de 70 à 160 caractères.'];
    elseif ($r['desc_len']<70)  $tips[] = ['medium','📄','Description trop courte ('.$r['desc_len'].' car.)','Pas assez d\'informations pour attirer les clics.','Allongez-la à 70–160 caractères.'];
    elseif ($r['desc_len']>160) $tips[] = ['medium','📄','Description trop longue ('.$r['desc_len'].' car.)','Google la tronquera dans les résultats.','Réduisez-la à 160 caractères maximum.'];
    if (count($r['h1'])===0)    $tips[] = ['high','🏷️','Aucune balise H1','Chaque page doit avoir exactement un H1 décrivant son sujet principal.','Ajoutez un seul <h1> avec le mot-clé principal de la page.'];
    elseif (count($r['h1'])>1)  $tips[] = ['medium','🏷️',count($r['h1']).' balises H1 détectées','Une seule balise H1 par page est recommandée par Google.','Gardez un seul H1 et convertissez les autres en H2 ou H3.'];
    if ($r['images_no_alt']>0)  $tips[] = ['medium','🖼️',$r['images_no_alt'].' image(s) sans attribut alt','L\'attribut alt est essentiel pour l\'accessibilité (RGAA) et le référencement des images.','Ajoutez alt="description de l\'image" à chaque balise <img>.'];
    if (!$r['robots'])          $tips[] = ['medium','🤖','Fichier robots.txt manquant','Sans robots.txt, les moteurs de recherche crawlent tout, y compris les pages à ne pas indexer.','Créez /robots.txt avec au minimum : User-agent: * / Allow: /'];
    if (!$r['sitemap'])         $tips[] = ['medium','🗺️','Sitemap XML manquant','Un sitemap aide Google à découvrir et indexer toutes vos pages rapidement.','Créez /sitemap.xml et soumettez-le dans Google Search Console.'];
    if (!$r['canonical'])       $tips[] = ['low','🔗','Balise canonical absente','Sans canonical, Google peut considérer plusieurs URLs comme du contenu dupliqué.','Ajoutez <link rel="canonical" href="URL-de-référence"> dans le <head>.'];
    if (!$r['mobile_viewport']) $tips[] = ['high','📱','Balise viewport manquante','Sans viewport, le site s\'affiche mal sur mobile. C\'est un critère de ranking Google depuis 2019.','Ajoutez <meta name="viewport" content="width=device-width, initial-scale=1.0"> dans le <head>.'];
    if (!$r['lang'])            $tips[] = ['low','🌍','Attribut lang manquant','Il aide les lecteurs d\'écran et les moteurs de recherche à identifier la langue.','Ajoutez lang="fr" (ou la langue appropriée) sur la balise <html>.'];
    if (!$r['og_title'])        $tips[] = ['low','📱','Open Graph (og:title) absent','Les balises OG définissent l\'aperçu du lien quand la page est partagée sur les réseaux sociaux.','Ajoutez <meta property="og:title" content="...">, og:description et og:image.'];
    if ($r['response_time']>1500) $tips[] = ['high','⚡','Temps de réponse lent ('.$r['response_time'].'ms)','Un TTFB > 1.5s pénalise directement le Core Web Vitals et le ranking Google.','Activez le cache serveur (OPcache, Redis), utilisez un CDN, ou améliorez votre hébergement.'];
    elseif ($r['response_time']>800) $tips[] = ['medium','⚡','Temps de réponse à optimiser ('.$r['response_time'].'ms)','En dessous de 800ms est recommandé pour un bon score Core Web Vitals.','Activez le cache serveur et optimisez vos requêtes base de données.'];
    if ($r['page_size']>512*1024) $tips[] = ['medium','📦','Page volumineuse ('.round($r['page_size']/1024).' Ko)','Une page lourde ralentit le chargement sur mobile et connexion lente.','Minifiez le HTML/CSS/JS, compressez avec gzip/brotli, réduisez les images inline.'];
    if ($r['scripts_ext']>5)    $tips[] = ['medium','📜',$r['scripts_ext'].' scripts externes détectés','Chaque script tiers ajoute une dépendance, un risque de sécurité et ralentit le chargement.','Regroupez, déférez (defer/async) ou hébergez localement les scripts critiques.'];
    if (!empty($r['links_broken'])) $tips[] = ['high','🔴',count($r['links_broken']).' lien(s) cassé(s) détecté(s)','Les liens 404 nuisent à l\'expérience utilisateur et au crawl budget de Google.','Corrigez ou supprimez : '.implode(', ', array_map(fn($l)=>$l['url'].' ('.$l['code'].')', array_slice($r['links_broken'],0,3)))];
    $missing = array_keys(array_filter($r['headers'], fn($v) => $v === null));
    if (!empty($missing)) $tips[] = ['medium','🛡️',count($missing).' en-tête(s) de sécurité manquant(s)','Ces en-têtes protègent vos visiteurs contre le clickjacking, XSS et autres attaques.','À ajouter dans la configuration de votre serveur web ('.implode(', ', $missing).').'];
    if (!$r['charset'])         $tips[] = ['low','🔤','Charset non déclaré','Sans charset, le navigateur peut mal interpréter les caractères spéciaux.','Ajoutez <meta charset="UTF-8"> en premier dans votre <head>.'];
    if (empty($r['technologies'])) $tips[] = ['low','🔧','Aucune technologie connue détectée','Aucun CMS, framework ou outil courant n\'a été identifié.','(Informatif — pas d\'action requise si votre site est custom)'];
    if (empty($tips)) $tips[] = ['low','✅','Excellent résultat !','Aucun problème majeur détecté. Continuez à maintenir ces bonnes pratiques.','Surveillez régulièrement avec Google Search Console et PageSpeed Insights.'];
    $order = ['high'=>0,'medium'=>1,'low'=>2];
    usort($tips, fn($a,$b) => $order[$a[0]] <=> $order[$b[0]]);
    return $tips;
}

include '../navbar.php';
?>
<!DOCTYPE html>
<html lang="fr">
<?php 

// 1. Mots-clés spécifiques pour booster la page Audit SEO
$page_title = "Audit SEO Site Internet à Épinal | Optimisation Sazulis";
$page_description = "Votre site web ne génère aucun clic ? Demandez un audit SEO complet à Sazulis (Épinal). Analyse technique, sémantique et stratégique pour booster votre trafic Google.";

// 2. Inclusion du head dynamique
include __DIR__ . '/../head.php'; 

?>
<style>
:root{--gold:#d4af37;--gold-light:#ffd700;--gold-bg:#fffbe6;--gold-border:#ffe9c6;--green:#22c55e;--orange:#f59e0b;--red:#ef4444;--text:#1a2347;--muted:#64748b;--radius:18px}
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',Arial,sans-serif;background:url('../assets/img/unique.png') center/cover no-repeat fixed;overflow-x:hidden}
.audit-wrap{min-height:100vh;padding:2em 1em 4em;display:flex;flex-direction:column;align-items:center}
.audit-hero{text-align:center;margin-bottom:2em}
.audit-hero h1{font-size:2.4em;font-weight:900;color:var(--text);margin:0 0 .3em}
.audit-hero p{font-size:1.1em;color:#555;margin:0}
.audit-hero span{color:#c8902e;font-weight:600}
.audit-card{background:rgba(255,255,255,.97);border-radius:24px;box-shadow:0 8px 32px #ffd70033,0 2px 8px #0002;border:2px solid var(--gold-border);padding:2.2em 2em;max-width:800px;width:100%}
.audit-form{display:flex;flex-direction:column;gap:1.1em}
.audit-form input[type="text"]{border-radius:12px;border:1.5px solid #ffd70099;padding:.8em 1.2em;font-size:1.05em;background:var(--gold-bg);outline:none;transition:border .2s;width:100%}
.audit-form input[type="text"]:focus{border:2px solid var(--gold)}
.audit-form label{display:flex;align-items:center;gap:.6em;font-size:1em;color:#444;cursor:pointer}
.audit-form button{background:linear-gradient(90deg,#ffe9c6,#fffbe6 70%,#ffd700);border:none;border-radius:999px;padding:.75em 2.5em;font-size:1.1em;font-weight:700;cursor:pointer;box-shadow:0 2px 12px #ffd70044;transition:transform .15s;align-self:flex-start}
.audit-form button:hover{transform:translateY(-2px) scale(1.03)}
.error-box{background:#fef2f2;border:1.5px solid #fca5a5;border-radius:12px;color:#b91c1c;padding:.9em 1.4em;margin:1em 0;font-size:1em;width:100%;max-width:800px}
.report-wrap{max-width:900px;width:100%;margin-top:2em}
.report-title{color:var(--text);font-size:1.4em;margin-bottom:1.2em;text-align:center;font-weight:700}
.report-url{color:var(--gold);word-break:break-all}

/* Scores */
.scores-row{display:flex;gap:1em;margin-bottom:2em;flex-wrap:wrap}
.score-card{flex:1 1 150px;background:#fff;border-radius:var(--radius);padding:1.3em 1em;text-align:center;box-shadow:0 2px 16px #0001;border:1.5px solid #f0f0f0}
.score-circle{width:86px;height:86px;margin:0 auto .6em;position:relative}
.score-circle svg{transform:rotate(-90deg)}
.score-circle .num{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.45em;font-weight:800;color:var(--text)}
.score-lbl{font-size:.88em;color:var(--muted);font-weight:600;letter-spacing:.5px;text-transform:uppercase}
.score-badge{display:inline-block;margin-top:.4em;font-size:.82em;font-weight:700;padding:.2em .9em;border-radius:999px}

/* Sections */
.report-grid{display:grid;grid-template-columns:1fr 1fr;gap:1.1em;margin-bottom:1.2em}
.report-section{background:#fff;border-radius:var(--radius);padding:1.4em;box-shadow:0 2px 12px #0001;border:1.5px solid #f0f0f0}
.report-section.full{grid-column:1/-1}
.report-section h3{margin:0 0 .9em;font-size:.92em;color:var(--gold);text-transform:uppercase;letter-spacing:.8px;font-weight:700;display:flex;align-items:center;gap:.5em;border-bottom:1px solid #f5f0e8;padding-bottom:.6em}

/* Tableau info */
.info-table{width:100%;border-collapse:collapse;font-size:.93em}
.info-table td{padding:.45em .3em;border-bottom:1px solid #f8f8f8;vertical-align:top}
.info-table td:first-child{color:var(--muted);font-weight:500;white-space:nowrap;padding-right:.8em;width:42%;font-size:.88em}

/* Badges */
.badge{display:inline-block;padding:.18em .85em;border-radius:999px;font-size:.85em;font-weight:600}
.badge.ok{background:#dcfce7;color:#166534}
.badge.bad{background:#fef2f2;color:#991b1b}
.badge.warn{background:#fef9c3;color:#854d0e}
.badge.neutral{background:#f1f5f9;color:#334155}

/* Tags */
.tag-list{display:flex;flex-wrap:wrap;gap:.4em;margin-top:.3em}
.tag{background:#f1f5f9;color:#334155;border-radius:999px;padding:.18em .8em;font-size:.83em;font-weight:500}

/* Recommandations */
.tips-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:1em}
.tip-item{border-radius:12px;padding:1em 1.2em;border-left:4px solid #eee;background:#fafafa}
.tip-item.high{border-left-color:var(--red);background:#fff8f8}
.tip-item.medium{border-left-color:var(--orange);background:#fffdf5}
.tip-item.low{border-left-color:var(--green);background:#f8fffe}
.tip-header{display:flex;align-items:center;gap:.7em;margin-bottom:.4em}
.tip-icon{font-size:1.2em}
.tip-title{font-weight:700;color:var(--text);font-size:1em}
.tip-prio{font-size:.75em;font-weight:700;padding:.15em .7em;border-radius:999px;margin-left:auto}
.tip-prio.high{background:#fee2e2;color:#b91c1c}
.tip-prio.medium{background:#fef3c7;color:#92400e}
.tip-prio.low{background:#dcfce7;color:#166534}
.tip-why{font-size:.9em;color:#555;margin-bottom:.4em}
.tip-fix{font-size:.88em;color:#1a2347;background:#fff;border-radius:8px;padding:.5em .8em;border:1px solid #e5e7eb}
.tip-fix strong{display:block;font-size:.8em;color:var(--muted);margin-bottom:.2em;text-transform:uppercase;letter-spacing:.5px}

/* Images liste */
.img-list{max-height:160px;overflow-y:auto;font-size:.85em;margin-top:.6em}
.img-row{display:flex;align-items:center;gap:.5em;padding:.22em 0;border-bottom:1px solid #f5f5f5}

/* Global table */
.global-table-wrap{overflow-x:auto;border-radius:12px;border:1px solid #f0f0f0}
.global-table{width:100%;border-collapse:collapse;font-size:.88em}
.global-table th{background:#f8fafc;padding:.6em .7em;text-align:left;color:var(--muted);font-weight:600;font-size:.82em;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}
.global-table td{padding:.5em .7em;border-top:1px solid #f1f5f9;vertical-align:middle}
.global-table tr:hover td{background:#fafafa}
.s-pill{display:inline-block;padding:.15em .65em;border-radius:999px;font-size:.82em;font-weight:700;color:#fff}

@media(max-width:700px){
    .report-grid{grid-template-columns:1fr}
    .scores-row{flex-direction:column}
    .audit-card{padding:1.2em .8em}
    .audit-hero h1{font-size:1.6em}
}
</style>
</head>
<body>
<div class="audit-wrap">

    <div class="audit-hero">
        <h1>🔍 Audit SEO &amp; Performance</h1>
        <p>Analyse complète : SEO, vitesse, sécurité, accessibilité, technologies.<br>
        <span>Rapport détaillé avec corrections à apporter.</span></p>
    </div>

    <?php if ($error): ?>
        <div class="error-box">⚠️ <?= h($error) ?></div>
    <?php endif; ?>

    <div class="audit-card">
        <form class="audit-form" method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
            <input type="text" name="site" placeholder="URL à auditer (ex: https://monsite.com)" required
                   value="<?= isset($_POST['site']) ? h($_POST['site']) : '' ?>">
            <label>
                <input type="checkbox" name="fullsite" value="1" <?= !empty($_POST['fullsite']) ? 'checked' : '' ?>>
                Auditer toutes les pages du site (via sitemap)
            </label>
            <button type="submit">🚀 Lancer l'audit</button>
        </form>
    </div>

<?php
// ══════════════════════════════════════════════════════════════════════════════
// RAPPORT PAGE UNIQUE
// ══════════════════════════════════════════════════════════════════════════════
if ($rapport):
    $r    = $rapport;
    $tips = buildTips($r);
    $scores = [['SEO',$r['seo_score']],['Performance',$r['perf_score']],['Sécurité',$r['sec_score']]];
?>
    <div class="report-wrap">
        <h2 class="report-title">Rapport d'audit — <span class="report-url"><?= h($r['url']) ?></span></h2>

        <!-- SCORES -->
        <div class="scores-row">
            <?php foreach ($scores as [$label,$score]):
                $color=$score>=75?'#22c55e':($score>=50?'#f59e0b':'#ef4444');
                $circ=2*M_PI*38; $dash=$circ*(1-$score/100);
                $isPerf = ($label === 'Performance');
            ?>
            <div class="score-card" <?= $isPerf ? 'id="perf-score-card"' : '' ?>>
                <div class="score-circle">
                    <svg width="86" height="86" viewBox="0 0 86 86">
                        <circle cx="43" cy="43" r="38" fill="none" stroke="#f1f5f9" stroke-width="9"/>
                        <circle cx="43" cy="43" r="38" fill="none" stroke="<?= $color ?>" stroke-width="9"
                            stroke-dasharray="<?= round($circ,2) ?>" stroke-dashoffset="<?= round($dash,2) ?>" stroke-linecap="round"/>
                    </svg>
                    <div class="num"><?= $score ?></div>
                </div>
                <div class="score-lbl"><?= $label ?></div>
                <span class="score-badge" style="background:<?= $color ?>22;color:<?= $color ?>;"><?= scoreLabel($score) ?></span>
                <?php if ($isPerf): ?>
                <div style="font-size:.72em;color:#94a3b8;margin-top:.3em">mesure en cours…</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="report-grid">

            <!-- Général -->
            <div class="report-section">
                <h3>📋 Général</h3>
                <table class="info-table">
                    <tr><td>HTTPS</td><td><?= badge($r['https'],'Activé ✅','Non activé ❌') ?></td></tr>
                    <tr><td>Code HTTP</td><td><?php $c=$r['status_code']??0; ?><span class="badge <?= $c===200?'ok':($c>=400?'bad':'warn') ?>"><?= $c?:'-' ?></span></td></tr>
                    <tr><td>Temps réponse</td><td><?php $rt=$r['response_time']; ?><span class="badge <?= $rt<800?'ok':($rt>1500?'bad':'warn') ?>"><?= $rt ?> ms</span></td></tr>
                    <tr><td>Taille page</td><td><?php $sz=round($r['page_size']/1024,1); ?><span class="badge <?= $sz<200?'ok':($sz>500?'bad':'warn') ?>"><?= $sz ?> Ko</span></td></tr>
                    <tr><td>Serveur</td><td><span class="badge neutral"><?= $r['server']?h($r['server']):'Inconnu' ?></span></td></tr>
                    <tr><td>Langue (lang=)</td><td><?= $r['lang']?'<span class="badge ok">'.h($r['lang']).'</span>':badge(false,'','Non définie ❌') ?></td></tr>
                    <tr><td>Charset</td><td><span class="badge neutral"><?= $r['charset']?h($r['charset']):'—' ?></span></td></tr>
                    <tr><td>Viewport mobile</td><td><?= badge($r['mobile_viewport'],'Présent ✅','Manquant ❌') ?></td></tr>
                    <tr><td>Favicon</td><td><?= badge($r['favicon'],'Présent ✅','Absent ❌') ?></td></tr>
                    <tr><td>robots.txt</td><td><?= badge($r['robots'],'Présent ✅','Absent ❌') ?></td></tr>
                    <tr><td>sitemap.xml</td><td><?= badge($r['sitemap'],'Présent ✅','Absent ❌') ?></td></tr>
                </table>
            </div>

            <!-- SEO On-page -->
            <div class="report-section">
                <h3>🎯 SEO On-page</h3>
                <table class="info-table">
                    <tr><td>Title (<?= $r['title_len'] ?> car.)</td><td>
                        <?php if($r['title']): $ok=$r['title_len']>=30&&$r['title_len']<=65; ?>
                            <span class="badge <?= $ok?'ok':'warn' ?>"><?= $ok?'✅ Parfait':'⚠️ Hors limites' ?></span>
                            <div style="font-size:.82em;color:#555;margin-top:.3em;word-break:break-word">"<?= h(mb_substr($r['title'],0,80)) ?>"</div>
                        <?php else: ?><?= badge(false,'','Absent ❌') ?><?php endif; ?>
                    </td></tr>
                    <tr><td>Description (<?= $r['desc_len'] ?> car.)</td><td>
                        <?php if($r['description']): $ok=$r['desc_len']>=70&&$r['desc_len']<=160; ?>
                            <span class="badge <?= $ok?'ok':'warn' ?>"><?= $ok?'✅ Parfait':'⚠️ Hors limites' ?></span>
                            <div style="font-size:.82em;color:#555;margin-top:.3em;word-break:break-word"><?= h(mb_substr($r['description'],0,100)) ?>…</div>
                        <?php else: ?><?= badge(false,'','Absent ❌') ?><?php endif; ?>
                    </td></tr>
                    <tr><td>H1 (<?= count($r['h1']) ?>)</td><td>
                        <?php $n=count($r['h1']); ?>
                        <span class="badge <?= $n===1?'ok':($n===0?'bad':'warn') ?>"><?= $n===1?'✅ 1 H1':($n===0?'❌ Aucun':'⚠️ '.$n.' H1') ?></span>
                        <?php foreach(array_slice($r['h1'],0,2) as $t): ?><div style="font-size:.82em;color:#555;margin-top:.2em">→ <?= h(mb_substr($t,0,55)) ?></div><?php endforeach; ?>
                    </td></tr>
                    <tr><td>H2 (<?= count($r['h2']) ?>)</td><td>
                        <?php if(empty($r['h2'])): ?><span class="badge warn">Aucun</span>
                        <?php else: foreach(array_slice($r['h2'],0,2) as $t): ?><div style="font-size:.82em;color:#555">→ <?= h(mb_substr($t,0,45)) ?></div><?php endforeach; endif; ?>
                        <?php if(count($r['h2'])>2): ?><div style="font-size:.78em;color:#999">+ <?= count($r['h2'])-2 ?> autres</div><?php endif; ?>
                    </td></tr>
                    <tr><td>H3 (<?= count($r['h3']) ?>)</td><td><span class="badge neutral"><?= count($r['h3']) ?> balise(s)</span></td></tr>
                    <tr><td>Canonical</td><td><?= badge(!empty($r['canonical']),'Présent ✅','Absent') ?></td></tr>
                    <tr><td>OG Title</td><td><?= badge(!empty($r['og_title']),'Présent ✅','Absent') ?></td></tr>
                    <tr><td>OG Description</td><td><?= badge(!empty($r['og_desc']),'Présent ✅','Absent') ?></td></tr>
                    <tr><td>Keywords</td><td><?= $r['keywords']?'<span class="badge neutral">'.h(mb_substr($r['keywords'],0,40)).'</span>':badge(false,'','—') ?></td></tr>
                </table>
            </div>

            <!-- Images -->
            <div class="report-section">
                <h3>🖼️ Images (<?= count($r['images']) ?>)</h3>
                <table class="info-table">
                    <tr><td>Total</td><td><span class="badge neutral"><?= count($r['images']) ?></span></td></tr>
                    <tr><td>Sans attribut alt</td><td>
                        <?= $r['images_no_alt']===0 ? badge(true,'✅ Toutes renseignées') : badge(false,'','❌ '.$r['images_no_alt'].' sans alt') ?>
                    </td></tr>
                </table>
                <?php if(!empty($r['images'])): ?>
                <div class="img-list">
                    <?php foreach(array_slice($r['images'],0,10) as $img): ?>
                    <div class="img-row">
                        <span><?= $img['has_alt']?'✅':'❌' ?></span>
                        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#555;font-size:.9em" title="<?= h($img['src']) ?>"><?= h(basename($img['src'])) ?></span>
                        <?php if(!$img['has_alt']): ?><span class="badge bad" style="font-size:.75em">alt manquant</span><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if(count($r['images'])>10): ?><div style="color:#999;padding:.3em 0">+ <?= count($r['images'])-10 ?> autres…</div><?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Liens & Performance -->
            <div class="report-section">
                <h3>🔗 Liens &amp; Performance</h3>
                <table class="info-table">
                    <tr><td>Liens totaux</td><td><span class="badge neutral"><?= $r['links_total'] ?></span></td></tr>
                    <tr><td>Liens cassés</td><td><?= badge(empty($r['links_broken']),'✅ Aucun','❌ '.count($r['links_broken']).' cassé(s)') ?></td></tr>
                    <tr><td>Scripts externes</td><td><?php $sc=$r['scripts_ext']; ?><span class="badge <?= $sc<=5?'ok':($sc<=10?'warn':'bad') ?>"><?= $sc ?></span></td></tr>
                </table>
                <?php if(!empty($r['links_broken'])): ?>
                <div style="margin-top:.7em;font-size:.83em;">
                    <?php foreach($r['links_broken'] as $bl): ?>
                    <div style="padding:.25em 0;color:#991b1b;border-bottom:1px solid #fef2f2">
                        ❌ <?= $bl['code'] ?> — <span title="<?= h($bl['url']) ?>"><?= h(mb_substr($bl['url'],0,50)) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Technologies -->
            <div class="report-section">
                <h3>🔧 Technologies détectées</h3>
                <?php if(!empty($r['technologies'])): ?>
                <div class="tag-list">
                    <?php foreach($r['technologies'] as $tech): ?><span class="tag"><?= h($tech) ?></span><?php endforeach; ?>
                </div>
                <?php else: ?><span class="badge neutral">Aucune connue détectée</span><?php endif; ?>
            </div>

            <!-- Sécurité -->
            <div class="report-section">
                <h3>🛡️ En-têtes de sécurité</h3>
                <table class="info-table">
                    <?php foreach($r['headers'] as $hname=>$hval): ?>
                    <tr>
                        <td><code style="font-size:.78em"><?= h($hname) ?></code></td>
                        <td><?= badge(!empty($hval),'Présent ✅','Absent ❌') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Performance navigateur (mesurée en JS) -->
            <div class="report-section full">
                <h3>⚡ Performance réelle (mesurée depuis votre navigateur)</h3>
                <div id="perf-browser-section">
                    <div style="text-align:center;padding:1.5em;color:#64748b;font-size:.95em">
                        ⏳ Chargement de la page cible en cours pour mesure…
                    </div>
                </div>
            </div>

            <!-- Recommandations -->
            <div class="report-section full">
                <h3>💡 Corrections à apporter
                    <span style="margin-left:auto;font-size:.85em;color:var(--muted);text-transform:none;letter-spacing:0;font-weight:400">
                        <?= count(array_filter($tips,fn($t)=>$t[0]==='high')) ?> critiques ·
                        <?= count(array_filter($tips,fn($t)=>$t[0]==='medium')) ?> importantes ·
                        <?= count(array_filter($tips,fn($t)=>$t[0]==='low')) ?> mineures
                    </span>
                </h3>
                <ul class="tips-list">
                    <?php foreach($tips as [$prio,$ico,$title,$why,$fix]): ?>
                    <li class="tip-item <?= $prio ?>">
                        <div class="tip-header">
                            <span class="tip-icon"><?= $ico ?></span>
                            <span class="tip-title"><?= h($title) ?></span>
                            <span class="tip-prio <?= $prio ?>"><?= $prio==='high'?'Critique':($prio==='medium'?'Important':'Mineur') ?></span>
                        </div>
                        <div class="tip-why"><?= h($why) ?></div>
                        <div class="tip-fix"><strong>✏️ Correction</strong><?= h($fix) ?></div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

        </div>
    </div>

<?php
// ══════════════════════════════════════════════════════════════════════════════
// RAPPORT MULTI-PAGES
// ══════════════════════════════════════════════════════════════════════════════
elseif($globalResults !== null):
    $avg = fn($k) => count($globalResults) ? round(array_sum(array_column($globalResults,$k))/count($globalResults)) : 0;
    $avgSeo=$avg('seo_score'); $avgPerf=$avg('perf_score'); $avgSec=$avg('sec_score');
?>
    <div class="report-wrap">
        <h2 class="report-title">Audit global — <?= count($globalResults) ?> pages analysées</h2>

        <div class="scores-row">
            <?php foreach([['SEO moyen',$avgSeo],['Perf. moyenne',$avgPerf],['Sécurité moyenne',$avgSec]] as [$label,$score]):
                $color=scoreColor($score); $circ=2*M_PI*38; $dash=$circ*(1-$score/100); ?>
            <div class="score-card">
                <div class="score-circle">
                    <svg width="86" height="86" viewBox="0 0 86 86">
                        <circle cx="43" cy="43" r="38" fill="none" stroke="#f1f5f9" stroke-width="9"/>
                        <circle cx="43" cy="43" r="38" fill="none" stroke="<?= $color ?>" stroke-width="9"
                            stroke-dasharray="<?= round($circ,2) ?>" stroke-dashoffset="<?= round($dash,2) ?>" stroke-linecap="round"/>
                    </svg>
                    <div class="num"><?= $score ?></div>
                </div>
                <div class="score-lbl"><?= $label ?></div>
                <span class="score-badge" style="background:<?= $color ?>22;color:<?= $color ?>;"><?= scoreLabel($score) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="audit-card" style="padding:1.4em;max-width:900px">
            <div class="global-table-wrap">
                <table class="global-table">
                    <thead><tr>
                        <th>Page</th><th>Statut</th><th>SEO</th><th>Perf.</th><th>Sécu.</th>
                        <th>Temps</th><th>Taille</th><th>HTTPS</th><th>H1</th><th>Title</th><th>Alt ❌</th><th>Liens ❌</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach($globalResults as $res):
                        $path=parse_url($res['url'],PHP_URL_PATH)?:'/';
                        if(strlen($path)>38) $path='…'.substr($path,-35);
                    ?>
                    <tr>
                        <td><a href="<?= h($res['url']) ?>" target="_blank" rel="noopener" style="color:var(--text);text-decoration:none;font-weight:500" title="<?= h($res['url']) ?>"><?= h($path) ?></a></td>
                        <td><?php $c=$res['status_code']??0; ?><span class="badge <?= $c===200?'ok':($c>=400?'bad':'warn') ?>"><?= $c?:'-' ?></span></td>
                        <td><span class="s-pill" style="background:<?= scoreColor($res['seo_score']) ?>"><?= $res['seo_score'] ?></span></td>
                        <td><span class="s-pill" style="background:<?= scoreColor($res['perf_score']) ?>"><?= $res['perf_score'] ?></span></td>
                        <td><span class="s-pill" style="background:<?= scoreColor($res['sec_score']) ?>"><?= $res['sec_score'] ?></span></td>
                        <td><?php $rt=$res['response_time']; ?><span class="badge <?= $rt<800?'ok':($rt>1500?'bad':'warn') ?>"><?= $rt ?>ms</span></td>
                        <td><?php $sz=round($res['page_size']/1024); ?><span class="badge <?= $sz<200?'ok':($sz>500?'bad':'warn') ?>"><?= $sz ?>Ko</span></td>
                        <td><?= $res['https']?'✅':'❌' ?></td>
                        <td><?php $h1c=count($res['h1']); ?><span class="badge <?= $h1c===1?'ok':($h1c===0?'bad':'warn') ?>"><?= $h1c ?></span></td>
                        <td><?= $res['title']?badge(true,'✅'):badge(false,'','❌') ?></td>
                        <td><?= $res['images_no_alt']===0?badge(true,'✅'):badge(false,'','❌ '.$res['images_no_alt']) ?></td>
                        <td><?= empty($res['links_broken'])?badge(true,'✅'):badge(false,'','❌ '.count($res['links_broken'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="margin-top:1em;font-size:.88em;color:var(--muted);text-align:center">
                💡 Cliquez sur une page pour l'ouvrir. Pour le détail complet d'une page, utilisez l'audit page unique.
            </p>
        </div>
    </div>
<?php endif; ?>

</div>

<script>
// ── Blocage mobile ────────────────────────────────────────────────────────────
(function(){
    if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)||window.innerWidth<900){
        document.body.innerHTML='<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;background:#fffbe6;"><div style="background:#fff;padding:2em 2.5em;border-radius:22px;box-shadow:0 2px 16px #ffd70044;text-align:center;max-width:400px"><h2 style="color:#d4af37;font-size:1.6em;margin-bottom:.8em">Audit disponible sur ordinateur</h2><p style="color:#1a2347;font-size:1em">Pour un rapport complet et lisible, utilisez un ordinateur ou un laptop.</p></div></div>';
        document.body.style.background='#fffbe6';
        return;
    }
}());

// ── Mesure de performance réelle depuis le navigateur ─────────────────────────
<?php if ($rapport): ?>
(function() {
    const targetUrl = <?= json_encode($rapport['url']) ?>;

    // Crée un bandeau d'attente
    const perfSection = document.getElementById('perf-browser-section');
    if (!perfSection) return;
    perfSection.innerHTML = '<div style="text-align:center;padding:1.5em;color:#64748b;font-size:.95em">⏳ Mesure des performances réelles en cours depuis votre navigateur…</div>';

    // Fonction qui charge l'URL cible dans un iframe caché et mesure via PerformanceObserver
    function measurePage(url, callback) {
        // On utilise fetch avec timing pour mesurer TTFB et taille réelle
        const results = {};

        // 1. Mesure TTFB et taille réelle via fetch
        const t0 = performance.now();
        fetch(url, { method: 'GET', mode: 'no-cors', cache: 'no-store' })
            .then(() => {
                results.ttfb = Math.round(performance.now() - t0);
            })
            .catch(() => {
                results.ttfb = null;
            })
            .finally(() => {
                // 2. Métriques PerformanceNavigationTiming via Resource Timing
                measureWithResourceTiming(url, results, callback);
            });
    }

    function measureWithResourceTiming(url, results, callback) {
        // Iframe caché pour charger la page cible et lire ses ressources
        const iframe = document.createElement('iframe');
        iframe.style.cssText = 'position:fixed;left:-9999px;top:-9999px;width:1280px;height:800px;opacity:0;pointer-events:none;border:none';
        iframe.setAttribute('sandbox', 'allow-same-origin allow-scripts');
        document.body.appendChild(iframe);

        const deadline = setTimeout(() => {
            collectIframeMetrics(iframe, results, callback);
        }, 12000); // timeout 12s max

        iframe.onload = function() {
            clearTimeout(deadline);
            // Laisse 500ms aux ressources de se charger
            setTimeout(() => collectIframeMetrics(iframe, results, callback), 800);
        };

        iframe.onerror = function() {
            clearTimeout(deadline);
            callback(results);
            iframe.remove();
        };

        try {
            iframe.src = url;
        } catch(e) {
            clearTimeout(deadline);
            callback(results);
            iframe.remove();
        }
    }

    function collectIframeMetrics(iframe, results, callback) {
        try {
            // Tente de lire les entrées de performance de l'iframe (same-origin uniquement)
            const iWin = iframe.contentWindow;
            const iPerf = iWin && iWin.performance;

            if (iPerf) {
                const nav = iPerf.getEntriesByType('navigation')[0];
                if (nav) {
                    results.dns          = Math.round(nav.domainLookupEnd - nav.domainLookupStart);
                    results.tcp          = Math.round(nav.connectEnd - nav.connectStart);
                    results.ttfb         = Math.round(nav.responseStart - nav.requestStart);
                    results.domLoad      = Math.round(nav.domContentLoadedEventEnd - nav.startTime);
                    results.fullLoad     = Math.round(nav.loadEventEnd - nav.startTime);
                    results.transferSize = nav.transferSize || 0;
                    results.encodedSize  = nav.encodedBodySize || 0;
                    results.decodedSize  = nav.decodedBodySize || 0;
                }

                // Ressources chargées
                const resources = iPerf.getEntriesByType('resource');
                let totalSize = 0, imgSize = 0, jsSize = 0, cssSize = 0, reqCount = resources.length;
                resources.forEach(r => {
                    const s = r.transferSize || 0;
                    totalSize += s;
                    if (r.initiatorType === 'img')        imgSize += s;
                    if (r.initiatorType === 'script')     jsSize  += s;
                    if (r.initiatorType === 'link' || r.initiatorType === 'css') cssSize += s;
                });
                results.reqCount  = reqCount;
                results.totalSize = totalSize;
                results.imgSize   = imgSize;
                results.jsSize    = jsSize;
                results.cssSize   = cssSize;

                // Core Web Vitals via PerformanceObserver si disponible
                // (LCP et CLS ne sont disponibles que pour la page courante, pas les iframes cross-origin)
                // On les approxime depuis les timings
                results.lcp_approx = results.fullLoad || results.domLoad;
            }
        } catch(e) {
            // Cross-origin : on ne peut pas lire l'iframe, on utilise les timings fetch
            results.crossOrigin = true;
        }

        // Mesure complémentaire via Performance Resource Timing sur la page principale
        // (pour les requêtes fetch/XHR faites depuis cette page)
        try {
            const entries = performance.getEntriesByType('resource');
            const entry = entries.find(e => e.name.startsWith(targetUrl.split('/').slice(0,3).join('/')));
            if (entry && !results.ttfb) {
                results.ttfb = Math.round(entry.responseStart - entry.startTime);
                results.transferSize = entry.transferSize || 0;
            }
        } catch(e) {}

        iframe.remove();
        callback(results);
    }

    function scorePerf(m) {
        let s = 100;
        const load = m.fullLoad || m.domLoad || m.ttfb || 9999;
        if (load > 5000)       s -= 50;
        else if (load > 3000)  s -= 35;
        else if (load > 2000)  s -= 20;
        else if (load > 1000)  s -= 10;
        const size = (m.totalSize || 0) / 1024;
        if (size > 3000)       s -= 25;
        else if (size > 1500)  s -= 15;
        else if (size > 700)   s -= 8;
        const req = m.reqCount || 0;
        if (req > 80)          s -= 20;
        else if (req > 50)     s -= 10;
        else if (req > 30)     s -= 5;
        return Math.max(0, Math.min(100, s));
    }

    function colorFor(s) {
        return s >= 75 ? '#22c55e' : s >= 50 ? '#f59e0b' : '#ef4444';
    }
    function labelFor(s) {
        return s >= 75 ? 'Bon' : s >= 50 ? 'Moyen' : 'Faible';
    }
    function fmt(n) { return n !== undefined && n !== null ? n.toLocaleString('fr') : '—'; }
    function fmtKo(bytes) { return bytes ? Math.round(bytes/1024).toLocaleString('fr') + ' Ko' : '—'; }
    function badgeHtml(val, okMax, warnMax, unit='ms') {
        if (val === null || val === undefined) return '<span class="badge neutral">—</span>';
        const v = parseInt(val);
        const cls = v <= okMax ? 'ok' : v <= warnMax ? 'warn' : 'bad';
        return `<span class="badge ${cls}">${fmt(val)} ${unit}</span>`;
    }

    measurePage(targetUrl, function(m) {
        const score = scorePerf(m);
        const color = colorFor(score);
        const circ  = 2 * Math.PI * 38;
        const dash  = circ * (1 - score / 100);
        const isXO  = m.crossOrigin;

        // Mise à jour de la jauge Performance
        const perfCard = document.getElementById('perf-score-card');
        if (perfCard) {
            perfCard.innerHTML = `
                <div class="score-circle">
                    <svg width="86" height="86" viewBox="0 0 86 86">
                        <circle cx="43" cy="43" r="38" fill="none" stroke="#f1f5f9" stroke-width="9"/>
                        <circle cx="43" cy="43" r="38" fill="none" stroke="${color}" stroke-width="9"
                            stroke-dasharray="${circ.toFixed(2)}" stroke-dashoffset="${dash.toFixed(2)}" stroke-linecap="round"/>
                    </svg>
                    <div class="num">${score}</div>
                </div>
                <div class="score-lbl">Performance</div>
                <span class="score-badge" style="background:${color}22;color:${color}">${labelFor(score)}</span>
                <div style="font-size:.72em;color:#94a3b8;margin-top:.3em">mesure navigateur réelle</div>
            `;
        }

        // Remplissage de la section perf navigateur
        const xoNote = isXO
            ? '<div style="font-size:.82em;color:#f59e0b;margin-bottom:.7em">⚠️ Site cross-origin : timings partiels (politique CORS). Les métriques de ressources nécessitent la même origine.</div>'
            : '';

        perfSection.innerHTML = `
            ${xoNote}
            <table class="info-table">
                <tr><td>DNS lookup</td><td>${badgeHtml(m.dns, 50, 150)}</td></tr>
                <tr><td>Connexion TCP</td><td>${badgeHtml(m.tcp, 100, 300)}</td></tr>
                <tr><td>TTFB (1er octet)</td><td>${badgeHtml(m.ttfb, 200, 600)}</td></tr>
                <tr><td>DOM chargé</td><td>${badgeHtml(m.domLoad, 1000, 2500)}</td></tr>
                <tr><td>Chargement total</td><td>${badgeHtml(m.fullLoad, 1500, 3500)}</td></tr>
                <tr><td>Taille transférée</td><td>${fmtKo(m.totalSize) !== '—' ? `<span class="badge ${(m.totalSize/1024)<700?'ok':(m.totalSize/1024)<2000?'warn':'bad'}">${fmtKo(m.totalSize)}</span>` : '<span class="badge neutral">—</span>'}</td></tr>
                <tr><td>Taille décodée</td><td><span class="badge neutral">${fmtKo(m.decodedSize)}</span></td></tr>
                <tr><td>Nb de requêtes</td><td>${m.reqCount !== undefined ? `<span class="badge ${m.reqCount<=30?'ok':m.reqCount<=60?'warn':'bad'}">${m.reqCount}</span>` : '<span class="badge neutral">—</span>'}</td></tr>
                <tr><td>Images</td><td><span class="badge neutral">${fmtKo(m.imgSize)}</span></td></tr>
                <tr><td>JavaScript</td><td><span class="badge neutral">${fmtKo(m.jsSize)}</span></td></tr>
                <tr><td>CSS</td><td><span class="badge neutral">${fmtKo(m.cssSize)}</span></td></tr>
            </table>
            <div style="margin-top:.8em;font-size:.78em;color:#94a3b8">
                ✅ Métriques mesurées depuis votre navigateur — plus fiables que la mesure serveur-à-serveur.
                Pour les Core Web Vitals officiels (LCP, CLS, INP), utilisez
                <a href="https://pagespeed.web.dev/analysis?url=${encodeURIComponent(targetUrl)}" target="_blank" rel="noopener" style="color:var(--gold)">PageSpeed Insights</a>.
            </div>
        `;
    });
})();
<?php endif; ?>
</script>

<?php include '../footer.php'; ?>
</body>
</html>