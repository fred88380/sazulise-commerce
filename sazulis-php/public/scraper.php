<?php
/**
 * scraper.php — Interface standalone scraper de sites obsolètes — Sazulis Dashboard
 * Appelle /public/proxy-ia.php (action=scraper_auto) en interne via JS.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
if (!$user || (($user['role'] ?? '') !== 'admin')) {
    http_response_code(403);
    exit('Accès refusé');
}

$config   = require dirname(__DIR__) . '/config/app.php';
$basePath = rtrim((string)(parse_url((string)($config['base_url'] ?? ''), PHP_URL_PATH) ?: ''), '/');
$proxyUrl = htmlspecialchars($basePath . '/proxy-ia.php', ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scraper Sazulis — Traqueur de sites obsolètes</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #08111a; color: #dceeff; font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100vh; }

    .s-header {
      background: linear-gradient(135deg, #0c1f2e, #1a0e0a);
      border-bottom: 1px solid rgba(255,255,255,0.1);
      padding: 1.2rem 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .s-header h1 {
      font-size: 1.3rem;
      font-weight: 800;
      letter-spacing: 0.05em;
      color: #ff6b6b;
    }

    .s-header a {
      color: #7ad3ff;
      text-decoration: none;
      font-size: 0.87rem;
    }

    .s-toolbar {
      background: rgba(255,255,255,0.03);
      border-bottom: 1px solid rgba(255,255,255,0.08);
      padding: 1rem 2rem;
      display: flex;
      gap: 0.75rem;
      align-items: center;
      flex-wrap: wrap;
    }

    .s-input {
      background: #0f1e2e;
      border: 1px solid rgba(255,255,255,0.14);
      color: #dceeff;
      border-radius: 9px;
      padding: 0.55rem 0.9rem;
      font-size: 0.9rem;
      outline: none;
      transition: border-color 0.2s;
    }
    .s-input:focus { border-color: #ff6b6b; }
    .s-input[placeholder*="Métier"] { width: 260px; }
    .s-input[placeholder*="Département"] { width: 200px; }

    .s-btn {
      border: none;
      border-radius: 9px;
      padding: 0.6rem 1.3rem;
      font-weight: 800;
      cursor: pointer;
      font-size: 0.88rem;
      transition: transform 0.15s ease, filter 0.15s ease;
    }
    .s-btn:hover { transform: translateY(-1px); filter: brightness(1.08); }
    .s-btn-search { background: #dc2626; color: #fff; }
    .s-btn-back { background: rgba(122,211,255,0.2); color: #7ad3ff; border: 1px solid rgba(122,211,255,0.3); }

    .s-main { padding: 2rem; max-width: 1400px; margin: 0 auto; }

    #s-status {
      text-align: center;
      padding: 3rem 1rem;
      color: rgba(255,255,255,0.4);
      border: 2px dashed rgba(255,255,255,0.1);
      border-radius: 18px;
      background: rgba(255,255,255,0.02);
    }

    #s-status p { margin-top: 0.75rem; font-size: 0.9rem; }

    .s-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.1rem;
    }

    .s-card {
      background: #0f1e2e;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 16px;
      padding: 1.2rem;
      transition: border-color 0.2s, transform 0.2s;
    }
    .s-card:hover { border-color: rgba(220,38,38,0.5); transform: translateY(-2px); }

    .s-score {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.25rem 0.65rem;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 0.75rem;
    }
    .s-score.critique { background: rgba(220,38,38,0.15); border: 1px solid rgba(220,38,38,0.4); color: #f87171; }
    .s-score.moyen { background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.4); color: #fbbf24; }

    .s-card h3 { font-size: 0.95rem; font-weight: 800; margin-bottom: 0.25rem; text-transform: uppercase; }
    .s-card-addr { font-size: 0.78rem; color: rgba(255,255,255,0.45); margin-bottom: 0.85rem; }

    .s-defauts { background: rgba(0,0,0,0.3); border-radius: 10px; padding: 0.7rem; margin-bottom: 0.85rem; }
    .s-defauts span { display: block; font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 0.4rem; font-weight: 700; }
    .s-defauts li { font-size: 0.78rem; color: #f87171; list-style: none; padding: 0.18rem 0; }
    .s-defauts li::before { content: '✗ '; }

    .s-meta { font-size: 0.78rem; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 0.75rem; display: grid; gap: 0.4rem; }
    .s-meta a { color: #60a5fa; text-decoration: none; }
    .s-meta a:hover { text-decoration: underline; }
    .s-meta .s-email { color: #fbbf24; font-weight: 700; }

    .s-actions { display: flex; gap: 0.5rem; margin-top: 0.85rem; }
    .s-act-btn {
      flex: 1; text-align: center; padding: 0.5rem 0.6rem;
      border-radius: 8px; font-size: 0.78rem; font-weight: 700;
      cursor: pointer; border: none; transition: opacity 0.15s;
      text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.3rem;
    }
    .s-act-btn:hover { opacity: 0.85; }
    .s-act-contact { background: rgba(122,211,255,0.18); color: #7ad3ff; }
    .s-act-blacklist { background: rgba(243,125,125,0.18); color: #f87171; }

    .s-spin { display: inline-block; width: 48px; height: 48px; border: 4px solid rgba(255,255,255,0.1); border-top-color: #dc2626; border-radius: 50%; animation: spin 0.8s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 600px) {
      .s-toolbar { flex-direction: column; align-items: stretch; }
      .s-input[placeholder*="Métier"], .s-input[placeholder*="Département"] { width: 100%; }
    }
  </style>
</head>
<body>

<header class="s-header">
  <h1>&#9762; Traqueur de sites obsolètes — Sazulis</h1>
  <a href="<?= htmlspecialchars($basePath . '/admin', ENT_QUOTES, 'UTF-8') ?>">&#8592; Retour au dashboard</a>
</header>

<div class="s-toolbar">
  <input id="s-secteur" type="text" class="s-input" placeholder="Métier (ex: Menuiserie, Garage, Plomberie)">
  <input id="s-ville" type="text" class="s-input" placeholder="Département ou CP (ex: 88, Lyon)">
  <button class="s-btn s-btn-search" onclick="lancerScan()">&#128269; Scanner les pépites</button>
</div>

<main class="s-main">
  <div id="s-status">
    <div style="font-size:2.5rem;margin-bottom:.5rem;">&#128270;</div>
    <p>Renseignez un secteur d'activité et une zone géographique, puis lancez le scan.<br>
    Seuls les sites techniquement obsolètes (non-responsive, HTTP, Flash…) seront affichés.</p>
  </div>
  <div id="s-grid" class="s-grid" style="display:none;"></div>
</main>

<script>
const PROXY = '<?= $proxyUrl ?>';

async function lancerScan() {
  const secteur = document.getElementById('s-secteur').value.trim();
  const ville   = document.getElementById('s-ville').value.trim();
  if (!secteur || !ville) { alert('Renseignez un métier ET une zone.'); return; }

  const status = document.getElementById('s-status');
  const grid   = document.getElementById('s-grid');
  grid.style.display = 'none';
  grid.innerHTML = '';
  status.style.display = 'block';
  status.innerHTML = '<div class="s-spin"></div><p>Analyse en cours — connexion à OSM, test des codes sources...</p>';

  const fd = new FormData();
  fd.append('action', 'scraper_auto');
  fd.append('secteur', secteur);
  fd.append('ville', ville);
  fd.append('limit', '25');

  try {
    const res  = await fetch(PROXY, { method: 'POST', body: fd });
    const data = await res.json();

    if (!data.ok || !data.results || data.results.length === 0) {
      status.innerHTML = '<p style="color:#fbbf24">&#9888; ' + (data.error || 'Aucun résultat. Modifie tes critères.') + '</p>';
      return;
    }

    status.style.display = 'none';
    grid.style.display   = 'grid';

    for (const p of data.results) {
      const scoreClass = p.score >= 60 ? 'critique' : 'moyen';
      const email1 = (p.emails && p.emails.length) ? p.emails[0] : '';
      const defauts = (p.pourquoi || '').replace('Défauts : ','').split(', ').filter(Boolean);
      const defautsHtml = defauts.map(d => `<li>${d}</li>`).join('');

      const card = document.createElement('div');
      card.className = 's-card';
      card.innerHTML = `
        <span class="s-score ${scoreClass}">Obsolescence ${p.score}/100</span>
        <h3>${p.nom}</h3>
        <div class="s-card-addr">${p.zone || ''} — ${p.plateforme || ''}</div>
        <div class="s-defauts"><span>Défauts identifiés</span><ul>${defautsHtml}</ul></div>
        <div class="s-meta">
          <div>&#128279; <a href="${p.url}" target="_blank">${p.url}</a></div>
          <div>&#128140; <span class="s-email">${email1 || 'Non trouvé'}</span></div>
        </div>
        <div class="s-actions">
          ${email1 ? `<a class="s-act-btn s-act-contact" href="mailto:${email1}?subject=Modernisation%20de%20votre%20site">&#9993; Démarcher</a>` : '<span class="s-act-btn" style="opacity:.4;cursor:default">Pas d\'email</span>'}
          <button class="s-act-btn s-act-blacklist" onclick="blacklist('${email1}', this)" ${email1?'':'disabled'}>&#128683; Blacklist</button>
        </div>`;
      grid.appendChild(card);
    }
  } catch (err) {
    status.innerHTML = '<p style="color:#f87171">Erreur réseau : ' + err.message + '</p>';
  }
}

async function blacklist(email, btn) {
  if (!email) return;
  const fd = new FormData();
  fd.append('action', 'blacklist_email');
  fd.append('email', email);
  const res  = await fetch(PROXY, { method: 'POST', body: fd });
  const data = await res.json();
  if (data.ok) { btn.textContent = '✓ Blacklisté'; btn.disabled = true; btn.style.opacity = '0.5'; }
}
</script>

</body>
</html>
