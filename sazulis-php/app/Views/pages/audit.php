<?php
$basePath = isset($basePath) ? (string) $basePath : '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function isUrlSafe(string $url, string $allowedDomain = ''): bool {
    $parsed = parse_url($url);
    if (!isset($parsed['scheme']) || !in_array(strtolower((string) $parsed['scheme']), ['http', 'https'], true)) {
        return false;
    }
    $host = (string) ($parsed['host'] ?? '');
    if ($host === '') {
        return false;
    }
    if ($allowedDomain !== '') {
        $norm = static fn (string $h): string => strtolower((string) preg_replace('/^www\./i', '', $h));
        if ($norm($host) === $norm($allowedDomain)) {
            return true;
        }
    }
    $ip = gethostbyname($host);
    if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
        return false;
    }
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function safeFetch(string $url, int $timeout = 15, string $allowedDomain = '') {
    if (!isUrlSafe($url, $allowedDomain)) {
        return false;
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => $timeout, 'follow_location' => 1, 'max_redirects' => 5, 'user_agent' => 'Mozilla/5.0 (AuditBot)'],
        'ssl' => ['verify_peer' => true],
    ]);
    return @file_get_contents($url, false, $ctx);
}

function safeGetHeaders(string $url, int $timeout = 10, string $allowedDomain = '') {
    if (!isUrlSafe($url, $allowedDomain)) {
        return false;
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => $timeout, 'follow_location' => 1, 'max_redirects' => 5, 'method' => 'HEAD', 'user_agent' => 'Mozilla/5.0 (AuditBot)'],
    ]);
    return @get_headers($url, 1, $ctx);
}

function extractUrlsFromSitemap(string $xml, string $allowedDomain, int $depth = 0): array {
    if ($depth > 2) {
        return [];
    }
    $urls = [];
    if (stripos($xml, '<sitemapindex') !== false) {
        if (preg_match_all('/<loc>([^<]+)<\/loc>/i', $xml, $sm)) {
            foreach ($sm[1] as $childUrl) {
                $child = safeFetch(trim((string) $childUrl), 10, $allowedDomain);
                if ($child) {
                    $urls = array_merge($urls, extractUrlsFromSitemap($child, $allowedDomain, $depth + 1));
                }
            }
        }
    } else {
        $clean = preg_replace('/<image:[^>]*>.*?<\/image:[^>]*>/is', '', $xml);
        if ($clean && preg_match_all('/<loc>([^<]+)<\/loc>/i', $clean, $lm)) {
            $urls = array_map('trim', $lm[1]);
        }
    }
    return $urls;
}

function auditPage(string $url, string $allowedDomain = ''): array {
    $r = [
        'url' => $url,
        'https' => stripos($url, 'https://') === 0,
        'response_time' => null,
        'status_code' => null,
        'page_size' => null,
        'server' => null,
        'headers' => [],
        'favicon' => false,
        'robots' => false,
        'sitemap' => false,
        'title' => null,
        'title_len' => 0,
        'description' => null,
        'desc_len' => 0,
        'canonical' => null,
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'images_no_alt' => 0,
        'links_broken' => [],
        'links_total' => 0,
        'scripts_ext' => 0,
        'seo_score' => 0,
        'perf_score' => 0,
        'sec_score' => 0,
        'error' => null,
    ];

    $secHeaders = ['Strict-Transport-Security', 'Content-Security-Policy', 'X-Frame-Options', 'X-Content-Type-Options', 'Referrer-Policy', 'Permissions-Policy'];

    $t0 = microtime(true);
    $headers = safeGetHeaders($url, 10, $allowedDomain);
    $r['response_time'] = round((microtime(true) - $t0) * 1000);

    if ($headers && is_array($headers)) {
        $statusLine = is_array($headers[0]) ? end($headers[0]) : $headers[0];
        preg_match('/HTTP\/[\d.]+ (\d+)/', (string) $statusLine, $sm);
        $r['status_code'] = isset($sm[1]) ? (int) $sm[1] : null;
        $r['server'] = $headers['Server'] ?? null;
        foreach ($secHeaders as $h) {
            $r['headers'][$h] = $headers[$h] ?? null;
        }
    } else {
        foreach ($secHeaders as $h) {
            $r['headers'][$h] = null;
        }
    }

    $html = safeFetch($url, 15, $allowedDomain);
    if ($html === false) {
        $r['error'] = 'Impossible de recuperer la page.';
        return $r;
    }

    $r['page_size'] = strlen($html);
    $base = rtrim((string) parse_url($url, PHP_URL_SCHEME) . '://' . (string) parse_url($url, PHP_URL_HOST), '/');

    $r['favicon'] = (bool) preg_match('/rel=["\'](?:shortcut icon|icon)["\'][^>]*>/i', $html);
    $robots = safeFetch($base . '/robots.txt', 5, $allowedDomain);
    $r['robots'] = ($robots !== false && strlen((string) $robots) > 10);
    foreach ([$base . '/sitemap-index.xml', $base . '/sitemap.xml'] as $sm) {
        $s = safeFetch($sm, 5, $allowedDomain);
        if ($s !== false && strlen((string) $s) > 10) {
            $r['sitemap'] = true;
            break;
        }
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $titleNode = $xpath->query('//title')->item(0);
    if ($titleNode) {
        $r['title'] = trim((string) $titleNode->textContent);
        $r['title_len'] = mb_strlen((string) $r['title']);
    }

    foreach ($xpath->query('//meta[@name="viewport"]') as $node) {
        $r['mobile_viewport'] = true;
        break;
    }

    foreach ($xpath->query('//meta[@name]') as $node) {
        if (!($node instanceof DOMElement)) {
            continue;
        }
        $name = strtolower($node->getAttribute('name'));
        $content = $node->getAttribute('content');
        if ($name === 'description') {
            $r['description'] = $content;
            $r['desc_len'] = mb_strlen($content);
        }
    }

    foreach ($xpath->query('//link[@rel="canonical"]') as $node) {
        if ($node instanceof DOMElement) {
            $r['canonical'] = $node->getAttribute('href');
            break;
        }
    }

    foreach (['h1', 'h2', 'h3'] as $tag) {
        foreach ($xpath->query('//' . $tag) as $node) {
            $r[$tag][] = trim((string) $node->textContent);
        }
    }

    foreach ($xpath->query('//img') as $img) {
        if (!($img instanceof DOMElement)) {
            continue;
        }
        if ($img->getAttribute('alt') === '') {
            $r['images_no_alt']++;
        }
    }

    foreach ($xpath->query('//script[@src]') as $node) {
        if ($node instanceof DOMElement && strpos($node->getAttribute('src'), 'http') === 0) {
            $r['scripts_ext']++;
        }
    }

    $linksChecked = 0;
    foreach ($xpath->query('//a[@href]') as $node) {
        if (!($node instanceof DOMElement)) {
            continue;
        }
        $href = $node->getAttribute('href');
        if ($href === '' || $href[0] === '#' || strpos($href, 'mailto:') === 0 || strpos($href, 'tel:') === 0) {
            continue;
        }
        $r['links_total']++;
        if ($linksChecked >= 15) {
            continue;
        }
        $fullUrl = strpos($href, 'http') === 0 ? $href : $base . '/' . ltrim($href, '/');
        if (!isUrlSafe($fullUrl, $allowedDomain)) {
            continue;
        }
        $lh = safeGetHeaders($fullUrl, 5, $allowedDomain);
        if ($lh) {
            $ls = is_array($lh[0]) ? end($lh[0]) : $lh[0];
            preg_match('/HTTP\/[\d.]+ (\d+)/', (string) $ls, $lm);
            $code = isset($lm[1]) ? (int) $lm[1] : 0;
            if ($code >= 400) {
                $r['links_broken'][] = ['url' => $fullUrl, 'code' => $code];
            }
        }
        $linksChecked++;
    }

    $seo = 0;
    if ($r['title'] && $r['title_len'] >= 30 && $r['title_len'] <= 65) { $seo += 20; } elseif ($r['title']) { $seo += 10; }
    if ($r['description'] && $r['desc_len'] >= 70 && $r['desc_len'] <= 160) { $seo += 20; } elseif ($r['description']) { $seo += 10; }
    if (count($r['h1']) === 1) { $seo += 15; } elseif (count($r['h1']) > 1) { $seo += 5; }
    if (!empty($r['h2'])) { $seo += 10; }
    if ($r['favicon']) { $seo += 5; }
    if ($r['robots']) { $seo += 5; }
    if ($r['sitemap']) { $seo += 5; }
    if ($r['canonical']) { $seo += 5; }
    if ($r['mobile_viewport']) { $seo += 5; }
    if ($r['images_no_alt'] === 0) { $seo += 5; }
    $r['seo_score'] = min(100, $seo);

    $perf = 100;
    if ((int) $r['response_time'] > 3000) { $perf -= 40; } elseif ((int) $r['response_time'] > 1500) { $perf -= 20; } elseif ((int) $r['response_time'] > 800) { $perf -= 10; }
    if ((int) $r['page_size'] > 1024 * 1024) { $perf -= 30; } elseif ((int) $r['page_size'] > 512 * 1024) { $perf -= 15; }
    if ((int) $r['scripts_ext'] > 10) { $perf -= 20; } elseif ((int) $r['scripts_ext'] > 5) { $perf -= 10; }
    $r['perf_score'] = max(0, $perf);

    $sec = 0;
    if ($r['https']) { $sec += 30; }
    $sec += min(60, count(array_filter($r['headers'])) * 10);
    if (!empty($r['headers']['Strict-Transport-Security'])) { $sec += 10; }
    $r['sec_score'] = min(100, $sec);

    return $r;
}

function scoreColor(int $s): string { return $s >= 75 ? '#22c55e' : ($s >= 50 ? '#f59e0b' : '#ef4444'); }
function scoreLabel(int $s): string { return $s >= 75 ? 'Bon' : ($s >= 50 ? 'Moyen' : 'Faible'); }
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$rapport = null;
$error = null;
$globalResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals((string) $_SESSION['csrf_token'], (string) $_POST['csrf_token'])) {
        $error = 'Requete invalide (CSRF).';
    } else {
        $rawSite = trim((string) ($_POST['site'] ?? ''));
        if ($rawSite === '') {
            $error = 'Veuillez saisir une URL.';
        } else {
            if (!preg_match('#^https?://#i', $rawSite)) {
                $rawSite = 'https://' . $rawSite;
            }
            $allowedDomain = (string) parse_url($rawSite, PHP_URL_HOST);
            if (!isUrlSafe($rawSite, $allowedDomain)) {
                $error = 'Cette URL n\'est pas autorisee.';
            } else {
                if (!empty($_POST['fullsite'])) {
                    $urls = [];
                    $baseRoot = rtrim($rawSite, '/');
                    foreach ([$baseRoot . '/sitemap-index.xml', $baseRoot . '/sitemap.xml'] as $candidate) {
                        $xml = safeFetch($candidate, 10, $allowedDomain);
                        if ($xml && strlen((string) $xml) > 10) {
                            $urls = extractUrlsFromSitemap($xml, $allowedDomain);
                            break;
                        }
                    }
                    $urls = array_values(array_unique(array_filter($urls, static fn (string $u): bool => isUrlSafe($u, $allowedDomain))));
                    $urls = array_slice($urls, 0, 30);
                    if (empty($urls)) {
                        $urls = [$rawSite];
                    }
                    $globalResults = array_map(static fn (string $u): array => auditPage($u, $allowedDomain), $urls);
                } else {
                    $rapport = auditPage($rawSite, $allowedDomain);
                    if (!empty($rapport['error'])) {
                        $error = $rapport['error'];
                        $rapport = null;
                    }
                }
            }
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
.audit-wrap{max-width:980px;margin:0 auto}
.audit-card{background:rgba(255,255,255,.97);border-radius:24px;box-shadow:0 8px 32px #ffd70033,0 2px 8px #0002;border:2px solid #ffe9c6;padding:1.4em}
.audit-form{display:flex;flex-direction:column;gap:1em}
.audit-form input[type="text"]{border-radius:12px;border:1.5px solid #ffd70099;padding:.8em 1.2em;font-size:1.05em;background:#fffbe6;outline:none;transition:border .2s;width:100%}
.audit-form input[type="text"]:focus{border:2px solid #d4af37}
.error-box{background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;color:#b91c1c;padding:.9em 1.2em;margin:1em 0}
.report-wrap{margin-top:1.2em}
.report-title{font-size:1.25em;color:#1a2347;text-align:center;margin-bottom:.9em}
.scores-row{display:flex;gap:1em;flex-wrap:wrap;margin:1em 0}
.score-card{flex:1 1 170px;background:#fff;border-radius:18px;padding:1em;text-align:center;border:1px solid #f0f0f0}
.score-circle{width:86px;height:86px;margin:0 auto .6em;position:relative}
.score-circle svg{transform:rotate(-90deg)}
.score-circle .num{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:1.45em;font-weight:800;color:#1a2347}
.score-lbl{font-size:.88em;color:#64748b;font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.score-badge{display:inline-block;margin-top:.4em;font-size:.82em;font-weight:700;padding:.2em .9em;border-radius:999px}
.report-grid{display:grid;grid-template-columns:1fr 1fr;gap:1em}
.report-section{background:#fff;border-radius:18px;padding:1.2em;border:1px solid #f0f0f0}
.report-section.full{grid-column:1/-1}
.report-section h3{margin:0 0 .8em;font-size:.92em;color:#c8902e;text-transform:uppercase;letter-spacing:.8px;border-bottom:1px solid #f5f0e8;padding-bottom:.55em}
.info-table{width:100%;border-collapse:collapse;font-size:.92em}
.info-table td{padding:.45em .3em;border-bottom:1px solid #f8f8f8;vertical-align:top}
.info-table td:first-child{color:#64748b;font-weight:500;white-space:nowrap;padding-right:.8em;width:42%;font-size:.88em}
.badge{display:inline-block;padding:.18em .85em;border-radius:999px;font-size:.85em;font-weight:600}
.badge.ok{background:#dcfce7;color:#166534}.badge.bad{background:#fef2f2;color:#991b1b}.badge.warn{background:#fef9c3;color:#854d0e}.badge.neutral{background:#f1f5f9;color:#334155}
.global-table-wrap{overflow-x:auto;border-radius:12px;border:1px solid #f0f0f0;background:#fff}
.global-table{width:100%;border-collapse:collapse;font-size:.88em}
.global-table th{background:#f8fafc;padding:.6em .7em;text-align:left;color:#64748b;font-weight:600;font-size:.82em;white-space:nowrap}
.global-table td{padding:.5em .7em;border-top:1px solid #f1f5f9}
.s-pill{display:inline-block;padding:.15em .65em;border-radius:999px;font-size:.82em;font-weight:700;color:#fff}
@media(max-width:900px){.report-grid{grid-template-columns:1fr}.scores-row{flex-direction:column}}
</style>

<div class="audit-wrap">
  <section class="catalog-head">
    <h1>Audit SEO et Performance</h1>
    <p>Moteur audit legacy adapte a la nouvelle version.</p>
  </section>

  <?php if ($error): ?>
    <div class="error-box">⚠️ <?= h($error) ?></div>
  <?php endif; ?>

  <div class="audit-card">
    <form class="audit-form" method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= h((string) $_SESSION['csrf_token']) ?>">
      <input type="text" name="site" placeholder="URL a auditer (ex: https://monsite.com)" required value="<?= isset($_POST['site']) ? h((string) $_POST['site']) : '' ?>">
      <label><input type="checkbox" name="fullsite" value="1" <?= !empty($_POST['fullsite']) ? 'checked' : '' ?>> Auditer toutes les pages du site (via sitemap)</label>
      <button type="submit" class="btn btn-primary">Lancer l'audit</button>
    </form>
  </div>

  <?php if ($rapport): ?>
    <div class="report-wrap">
      <h2 class="report-title">Rapport d'audit — <?= h((string) $rapport['url']) ?></h2>
      <div class="scores-row">
        <?php foreach ([['SEO', (int) $rapport['seo_score']], ['Performance', (int) $rapport['perf_score']], ['Securite', (int) $rapport['sec_score']]] as [$label, $score]):
          $color = scoreColor((int) $score);
          $circ = 2 * M_PI * 38;
          $dash = $circ * (1 - ((int) $score / 100));
        ?>
          <div class="score-card">
            <div class="score-circle">
              <svg width="86" height="86" viewBox="0 0 86 86">
                <circle cx="43" cy="43" r="38" fill="none" stroke="#f1f5f9" stroke-width="9"/>
                <circle cx="43" cy="43" r="38" fill="none" stroke="<?= $color ?>" stroke-width="9" stroke-dasharray="<?= round($circ, 2) ?>" stroke-dashoffset="<?= round($dash, 2) ?>" stroke-linecap="round"/>
              </svg>
              <div class="num"><?= (int) $score ?></div>
            </div>
            <div class="score-lbl"><?= h((string) $label) ?></div>
            <span class="score-badge" style="background:<?= $color ?>22;color:<?= $color ?>"><?= scoreLabel((int) $score) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="report-grid">
        <div class="report-section">
          <h3>General</h3>
          <table class="info-table">
            <tr><td>HTTPS</td><td><span class="badge <?= !empty($rapport['https']) ? 'ok' : 'bad' ?>"><?= !empty($rapport['https']) ? 'Oui' : 'Non' ?></span></td></tr>
            <tr><td>Code HTTP</td><td><span class="badge neutral"><?= (int) ($rapport['status_code'] ?? 0) ?: '-' ?></span></td></tr>
            <tr><td>Temps reponse</td><td><span class="badge neutral"><?= (int) ($rapport['response_time'] ?? 0) ?> ms</span></td></tr>
            <tr><td>Taille page</td><td><span class="badge neutral"><?= round(((int) ($rapport['page_size'] ?? 0)) / 1024, 1) ?> Ko</span></td></tr>
            <tr><td>Liens totaux</td><td><span class="badge neutral"><?= (int) ($rapport['links_total'] ?? 0) ?></span></td></tr>
            <tr><td>Liens casses</td><td><span class="badge <?= empty($rapport['links_broken']) ? 'ok' : 'bad' ?>"><?= count((array) ($rapport['links_broken'] ?? [])) ?></span></td></tr>
          </table>
        </div>
        <div class="report-section">
          <h3>SEO</h3>
          <table class="info-table">
            <tr><td>Title</td><td><span class="badge <?= !empty($rapport['title']) ? 'ok' : 'bad' ?>"><?= !empty($rapport['title']) ? 'Present' : 'Absent' ?></span></td></tr>
            <tr><td>Description</td><td><span class="badge <?= !empty($rapport['description']) ? 'ok' : 'bad' ?>"><?= !empty($rapport['description']) ? 'Presente' : 'Absente' ?></span></td></tr>
            <tr><td>H1</td><td><span class="badge <?= count((array) ($rapport['h1'] ?? [])) === 1 ? 'ok' : 'warn' ?>"><?= count((array) ($rapport['h1'] ?? [])) ?></span></td></tr>
            <tr><td>Images sans alt</td><td><span class="badge <?= ((int) ($rapport['images_no_alt'] ?? 0)) === 0 ? 'ok' : 'bad' ?>"><?= (int) ($rapport['images_no_alt'] ?? 0) ?></span></td></tr>
          </table>
        </div>
      </div>
    </div>
  <?php elseif ($globalResults !== null):
    $avg = static fn (string $k): int => count($globalResults) ? (int) round(array_sum(array_column($globalResults, $k)) / count($globalResults)) : 0;
  ?>
    <div class="report-wrap">
      <h2 class="report-title">Audit global — <?= count($globalResults) ?> pages analysees</h2>
      <div class="scores-row">
        <?php foreach ([['SEO moyen', $avg('seo_score')], ['Perf moyenne', $avg('perf_score')], ['Securite moyenne', $avg('sec_score')]] as [$label, $score]): ?>
          <div class="score-card">
            <div class="score-lbl"><?= h((string) $label) ?></div>
            <div class="num" style="font-size:2rem;color:<?= scoreColor((int) $score) ?>"><?= (int) $score ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="global-table-wrap">
        <table class="global-table">
          <thead><tr><th>Page</th><th>HTTP</th><th>SEO</th><th>Perf</th><th>Securite</th><th>Temps</th><th>Alt</th></tr></thead>
          <tbody>
            <?php foreach ($globalResults as $res): ?>
              <tr>
                <td><a class="link" href="<?= h((string) ($res['url'] ?? '')) ?>" target="_blank" rel="noopener"><?= h((string) (parse_url((string) ($res['url'] ?? ''), PHP_URL_PATH) ?: '/')) ?></a></td>
                <td><?= (int) ($res['status_code'] ?? 0) ?: '-' ?></td>
                <td><?= (int) ($res['seo_score'] ?? 0) ?></td>
                <td><?= (int) ($res['perf_score'] ?? 0) ?></td>
                <td><?= (int) ($res['sec_score'] ?? 0) ?></td>
                <td><?= (int) ($res['response_time'] ?? 0) ?>ms</td>
                <td><?= (int) ($res['images_no_alt'] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
</div>
