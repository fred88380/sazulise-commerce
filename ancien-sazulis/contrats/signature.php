<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier

if (empty($_SESSION['user_id'])) {
  http_response_code(403);
  die("Non connecté.");
}
$userId  = (int)$_SESSION['user_id'];

$projetId = isset($_GET['projet_id']) ? (int)$_GET['projet_id'] : 0;
if ($projetId <= 0) { http_response_code(400); die("projet_id manquant"); }

require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) die("DB KO");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// check projet appartient au user
$stmt = $pdo->prepare("SELECT id FROM projets WHERE id=? AND id_utilisateur=?");
$stmt->execute([$projetId, $userId]);
if (!$stmt->fetchColumn()) {
  http_response_code(403);
  die("Projet introuvable ou interdit.");
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Signer le contrat</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:system-ui,Arial;margin:0;background:#fbfaf7;}
  .wrap{max-width:980px;margin:0 auto;padding:18px;}
  .card{background:#fff;border:1px solid #e7cf9c;border-radius:16px;padding:16px;box-shadow:0 6px 18px #0001;}
  h1{margin:0 0 10px;color:#b8860b;font-size:20px;}
  .row{display:flex;gap:14px;flex-wrap:wrap;align-items:flex-start;}
  .col{flex:1 1 320px;}
  .canvasBox{border:2px dashed #e7cf9c;border-radius:14px;padding:10px;background:#fff;}
  canvas{width:100%;height:220px;display:block;background:#fff;border-radius:12px;}
  .btn{border:none;border-radius:12px;padding:10px 12px;font-weight:800;cursor:pointer}
  .btn.primary{background:linear-gradient(90deg,#ffe9c6,#fffbe6 80%,#ffd700);color:#333;}
  .btn.ghost{background:#f5f5f5;}
  .btn.danger{background:#ffd7d7;color:#a00;}
  .muted{color:#777;font-size:13px}
  .panel{border:1px solid #eee;border-radius:14px;padding:12px;background:#fff;}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  label{font-size:12px;color:#555;font-weight:700}
  input[type="number"]{width:100%;padding:8px;border-radius:10px;border:1px solid #ddd;background:#fffbe6;}
  .previewBox{border:1px solid #eee;border-radius:14px;padding:12px;background:#fff;position:relative;min-height:260px;overflow:hidden;}
  .signatureOverlay{
    position:absolute; left:0; top:0;
    width:160px; height:auto;
    border:2px solid #ffd70088;
    border-radius:10px;
    cursor:move;
    user-select:none;
    touch-action:none;
    background:#fff;
  }
  .hint{font-size:12px;color:#666;margin-top:8px}

  /* Galerie */
  .gallery{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px}
  .sigCard{
    width:160px;border:1px solid #eee;border-radius:12px;padding:8px;background:#fff;
    cursor:pointer;transition:transform .12s ease, box-shadow .12s ease;
  }
  .sigCard:hover{transform:translateY(-1px);box-shadow:0 6px 16px #0001}
  .sigCard img{width:100%;border-radius:10px;background:#fff;border:1px solid #f2f2f2}
  .sigCard .date{font-size:12px;color:#777;margin-top:6px}
  .sigCard.active{border-color:#ffd700;box-shadow:0 0 0 2px #ffd70044 inset}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>✍️ Signer le contrat</h1>
    <div class="muted">Projet #<?= (int)$projetId ?> — Dessine ta signature, puis place-la sur le PDF.</div>
    <div style="height:12px"></div>

    <div class="row">
      <!-- Dessin signature -->
      <div class="col">
        <div class="canvasBox">
          <canvas id="sigCanvas" width="900" height="320"></canvas>
        </div>

        <div style="display:flex;gap:10px;margin-top:10px;flex-wrap:wrap;">
          <button class="btn ghost" id="clearBtn">Effacer</button>
          <button class="btn primary" id="saveBtn">Enregistrer</button>

          <a class="btn ghost" href="contrat.php?projet_id=<?= (int)$projetId ?>" target="_blank" rel="noopener"
             style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;">
            Voir le PDF
          </a>

          <button class="btn primary" id="validateBtn">Valider</button>
        </div>

        <div class="hint">Astuce : signe en grand, puis tu pourras redimensionner ensuite.</div>
        <div class="hint">“Enregistrer” = ajoute une signature à l’historique. “Valider” = marque le contrat signé + retour au profil.</div>
      </div>

      <!-- Placement / redimension -->
      <div class="col">
        <div class="panel">
          <b>Placement sur le PDF</b>
          <div class="muted" style="margin-top:6px">Tu peux déplacer la signature (glisser) et ajuster largeur/position.La position prés régler est deja defini selon la hauteur la largeur et selon l'emplacement de la signature </div>

          <div style="height:10px"></div>

          <div class="grid">
            <div>
              <label>X (mm)</label>
              <input type="number" id="xInput" step="1" value="125">
            </div>
            <div>
              <label>Y (mm)</label>
              <input type="number" id="yInput" step="1" value="193">
            </div>
            <div>
              <label>Largeur (mm)</label>
              <input type="number" id="wInput" step="1" value="55">
            </div>
            <div>
              <label>&nbsp;</label>
              <button class="btn primary" id="savePosBtn" style="width:100%">Sauver placement</button>
            </div>
          </div>

          <div style="height:12px"></div>

          <div class="previewBox" id="previewBox">
            <div class="muted">Prévisualisation (drag & resize avec le champ largeur)</div>
            <img id="sigImg" class="signatureOverlay" src="" alt="signature" style="display:none;">
          </div>

          <div class="hint">
            Le placement est stocké en DB (contrats.cfg_json) et utilisé dans <code>contrat.php</code>.
          </div>

          <div style="height:12px"></div>

          <div class="panel">
            <b>Mes signatures enregistrées</b>
            <div class="muted" style="margin-top:6px">
              Clique sur une signature pour la mettre comme signature active du contrat.
            </div>
            <div id="gallery" class="gallery"></div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<script>
const projetId = <?= (int)$projetId ?>;

const canvas = document.getElementById('sigCanvas');
const ctx = canvas.getContext('2d');

ctx.lineWidth = 3;
ctx.lineCap = 'round';
ctx.strokeStyle = '#111';

let drawing = false;
let last = {x:0,y:0};

function getPos(e){
  const r = canvas.getBoundingClientRect();
  const t = (e.touches && e.touches[0]) ? e.touches[0] : e;
  return { x: (t.clientX - r.left) * (canvas.width / r.width),
           y: (t.clientY - r.top) * (canvas.height / r.height) };
}

function start(e){
  drawing = true;
  last = getPos(e);
  e.preventDefault();
}
function move(e){
  if(!drawing) return;
  const p = getPos(e);
  ctx.beginPath();
  ctx.moveTo(last.x,last.y);
  ctx.lineTo(p.x,p.y);
  ctx.stroke();
  last = p;
  e.preventDefault();
}
function end(){ drawing = false; }

canvas.addEventListener('mousedown', start);
canvas.addEventListener('mousemove', move);
window.addEventListener('mouseup', end);

canvas.addEventListener('touchstart', start, {passive:false});
canvas.addEventListener('touchmove', move, {passive:false});
canvas.addEventListener('touchend', end);

document.getElementById('clearBtn').addEventListener('click', ()=>{
  ctx.clearRect(0,0,canvas.width,canvas.height);
});

async function postJSON(url, data){
  const res = await fetch(url, {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(data)
  });
  const txt = await res.text();
  let json = null;
  try { json = JSON.parse(txt); } catch(e){ /* ignore */ }
  if(!res.ok){
    throw new Error((json && json.error) ? json.error : txt);
  }
  return json || {};
}

const sigImg = document.getElementById('sigImg');
const previewBox = document.getElementById('previewBox');

const xInput = document.getElementById('xInput');
const yInput = document.getElementById('yInput');
const wInput = document.getElementById('wInput');

function applyOverlay(){
  const x = parseFloat(xInput.value||'0');
  const y = parseFloat(yInput.value||'0');
  const w = parseFloat(wInput.value||'55');

  const pxPerMm = 2.6; // approximation visuelle
  sigImg.style.left = (x * pxPerMm * 0.25) + 'px';
  sigImg.style.top  = (y * pxPerMm * 0.25) + 'px';
  sigImg.style.width = (w * pxPerMm) + 'px';
}

[xInput,yInput,wInput].forEach(el=>el.addEventListener('input', applyOverlay));

let drag = false;
let dragOffset = {x:0,y:0};

sigImg.addEventListener('pointerdown', (e)=>{
  drag = true;
  sigImg.setPointerCapture(e.pointerId);
  const r = sigImg.getBoundingClientRect();
  dragOffset.x = e.clientX - r.left;
  dragOffset.y = e.clientY - r.top;
});

sigImg.addEventListener('pointermove', (e)=>{
  if(!drag) return;
  const pr = previewBox.getBoundingClientRect();
  let left = (e.clientX - pr.left - dragOffset.x);
  let top  = (e.clientY - pr.top - dragOffset.y);
  left = Math.max(0, Math.min(left, pr.width - 20));
  top  = Math.max(0, Math.min(top, pr.height - 20));
  sigImg.style.left = left+'px';
  sigImg.style.top  = top+'px';

  const pxPerMm = 2.6;
  xInput.value = Math.round((left / (pxPerMm*0.25)));
  yInput.value = Math.round((top  / (pxPerMm*0.25)));
});

sigImg.addEventListener('pointerup', ()=> drag=false);
sigImg.addEventListener('pointercancel', ()=> drag=false);

const gallery = document.getElementById('gallery');
let currentActivePath = null;

function renderGallery(list){
  gallery.innerHTML = '';
  if(!list || !list.length){
    gallery.innerHTML = '<div class="muted">Aucune signature enregistrée pour le moment.</div>';
    return;
  }

  list.forEach(s => {
    const box = document.createElement('div');
    box.className = 'sigCard' + ((currentActivePath && s.file_path === currentActivePath) ? ' active' : '');
    box.innerHTML = `
      <img src="${s.file_path}?t=${Date.now()}" />
      <div class="date">${s.created_at ?? ''}</div>
    `;

    box.addEventListener('click', async () => {
      try{
        const out = await postJSON('set_active_signature.php', {
          projet_id: projetId,
          signature_id: parseInt(s.id,10),
          cfg: {
            x: parseFloat(xInput.value||'125'),
            y: parseFloat(yInput.value||'235'),
            w: parseFloat(wInput.value||'55'),
          }
        });

        if(out.active_signature){
          currentActivePath = out.active_signature;
          sigImg.src = out.active_signature + '?t=' + Date.now();
          sigImg.style.display = 'block';
          applyOverlay();
          renderGallery(list); // refresh active highlight
        }
        alert(out.message || "Signature active mise à jour ✅");
      }catch(err){
        alert("Erreur: " + err.message);
      }
    });

    gallery.appendChild(box);
  });
}

document.getElementById('saveBtn').addEventListener('click', async ()=>{
  const dataURL = canvas.toDataURL('image/png');
  try{
    const out = await postJSON('save_signature.php', {
      projet_id: projetId,
      image: dataURL,
      cfg: {
        x: parseFloat(xInput.value||'125'),
        y: parseFloat(yInput.value||'235'),
        w: parseFloat(wInput.value||'55'),
      }
    });

    alert(out.message || "Signature enregistrée ✅");

    if(out.active_signature){
      currentActivePath = out.active_signature;
      sigImg.src = out.active_signature + '?t=' + Date.now();
      sigImg.style.display = 'block';
      applyOverlay();
    }
    if(out.signatures) renderGallery(out.signatures);

  }catch(err){
    alert("Erreur: " + err.message);
  }
});

document.getElementById('savePosBtn').addEventListener('click', async ()=>{
  try{
    const out = await postJSON('save_signature.php', {
      projet_id: projetId,
      cfg: {
        x: parseFloat(xInput.value||'125'),
        y: parseFloat(yInput.value||'235'),
        w: parseFloat(wInput.value||'55'),
      }
    });
    alert(out.message || "Placement sauvegardé ✅");

    if(out.active_signature){
      currentActivePath = out.active_signature;
      sigImg.src = out.active_signature + '?t=' + Date.now();
      sigImg.style.display = 'block';
      applyOverlay();
    }
    if(out.signatures) renderGallery(out.signatures);
  }catch(err){
    alert("Erreur: " + err.message);
  }
});

document.getElementById('validateBtn').addEventListener('click', async ()=>{
  try{
    // On valide le contrat : pas obligé de re-sauver une image.
    // (Si tu veux forcer l'enregistrement de ce qui est dessiné, tu peux envoyer image: canvas.toDataURL)
    const out = await postJSON('save_signature.php', {
      projet_id: projetId,
      finalize: true,
      cfg: {
        x: parseFloat(xInput.value||'125'),
        y: parseFloat(yInput.value||'235'),
        w: parseFloat(wInput.value||'55'),
      }
    });

    alert(out.message || "Contrat validé ✅");

    // retour profil
    if(out.redirect_url){
      window.location.href = out.redirect_url;
    } else {
      window.location.href = "../pages/profil.php#projets";
    }

  }catch(err){
    alert("Erreur: " + err.message);
  }
});

// Au chargement : récupère signature active + cfg + galerie
(async ()=>{
  try{
    const res = await fetch('load_signature.php?projet_id='+projetId, {cache:'no-store'});
    if(!res.ok) return;
    const j = await res.json();

    if(j.cfg){
      xInput.value = j.cfg.x ?? xInput.value;
      yInput.value = j.cfg.y ?? yInput.value;
      wInput.value = j.cfg.w ?? wInput.value;
    }

    if(j.active_signature){
      currentActivePath = j.active_signature;
      sigImg.src = j.active_signature + '?t=' + Date.now();
      sigImg.style.display = 'block';
    }

    applyOverlay();

    if(j.signatures) renderGallery(j.signatures);

  }catch(e){}
})();
</script>
</body>
</html>
