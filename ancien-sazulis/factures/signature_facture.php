<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
// signature_facture.php : page de signature pour une facture
session_start();
if (empty($_SESSION['user_id'])) {
  http_response_code(403);
  die("Non connecté.");
}
$userId  = (int)$_SESSION['user_id'];
$factureId = isset($_GET['facture_id']) ? (int)$_GET['facture_id'] : 0;
if ($factureId <= 0) { http_response_code(400); die("facture_id manquant"); }
require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) die("DB KO");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
// Vérifie que la facture appartient à l'utilisateur (via la commande)
$stmt = $pdo->prepare("SELECT f.id FROM factures f INNER JOIN commandes c ON f.id_commande = c.id WHERE f.id=? AND c.id_utilisateur=?");
$stmt->execute([$factureId, $userId]);
if (!$stmt->fetchColumn()) {
  http_response_code(403);
  die("Facture introuvable ou interdite.");
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Signer la facture</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body{font-family:system-ui,Arial;margin:0;background:#fbfaf7;}
  .wrap{max-width:980px;margin:0 auto;padding:18px;}
  .card{background:#fff;border:1px solid #e7cf9c;border-radius:16px;padding:16px;box-shadow:0 6px 18px #0001;}
  h1{margin:0 0 10px;color:#b8860b;font-size:20px;}
  .canvasBox{border:2px dashed #e7cf9c;border-radius:14px;padding:10px;background:#fff;}
  canvas{width:100%;height:220px;display:block;background:#fff;border-radius:12px;}
  .btn{border:none;border-radius:12px;padding:10px 12px;font-weight:800;cursor:pointer}
  .btn.primary{background:linear-gradient(90deg,#ffe9c6,#fffbe6 80%,#ffd700);color:#333;}
  .btn.ghost{background:#f5f5f5;}
  .btn.danger{background:#ffd7d7;color:#a00;}
  .muted{color:#777;font-size:13px}
</style>
</head>
<body>
<div class="wrap">
  <div class="card">
    <h1>✍️ Signer la facture</h1>
    <div class="muted">Facture #<?= (int)$factureId ?> — Dessine ta signature, puis enregistre-la.</div>
    <div class="canvasBox">
      <canvas id="signature" width="600" height="220"></canvas>
    </div>
    <button class="btn primary" id="saveBtn">Enregistrer la signature</button>
    <button class="btn ghost" id="clearBtn">Effacer</button>
    <div id="msg" class="muted"></div>
  </div>
</div>
<script>
const canvas = document.getElementById('signature');
const ctx = canvas.getContext('2d');
let drawing = false;
canvas.addEventListener('mousedown', e => { drawing = true; ctx.beginPath(); });
canvas.addEventListener('mouseup', e => { drawing = false; });
canvas.addEventListener('mouseout', e => { drawing = false; });
canvas.addEventListener('mousemove', draw);
function draw(e) {
  if (!drawing) return;
  const rect = canvas.getBoundingClientRect();
  ctx.lineWidth = 2;
  ctx.lineCap = 'round';
  ctx.strokeStyle = '#b8860b';
  ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
  ctx.stroke();
  ctx.beginPath();
  ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
}
document.getElementById('clearBtn').onclick = () => { ctx.clearRect(0,0,canvas.width,canvas.height); };
document.getElementById('saveBtn').onclick = () => {
  const data = canvas.toDataURL('image/png');
  fetch('save_signature_facture.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ facture_id: <?= (int)$factureId ?>, image: data })
  })
  .then(r => r.json())
  .then(res => {
    document.getElementById('msg').textContent = res.success ? 'Signature enregistrée !' : (res.error || 'Erreur');
    if(res.success) setTimeout(()=>window.close(), 1200);
  });
};
</script>
</body>
</html>
