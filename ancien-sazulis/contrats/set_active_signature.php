<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier

function out(int $code, array $payload){
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

if (empty($_SESSION['user_id'])) out(403, ['error'=>'Non connecté']);
$userId = (int)$_SESSION['user_id'];

$data = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($data)) out(400, ['error'=>'JSON invalide']);

$projetId = isset($data['projet_id']) ? (int)$data['projet_id'] : 0;
$sigId = isset($data['signature_id']) ? (int)$data['signature_id'] : 0;
$cfg = (isset($data['cfg']) && is_array($data['cfg'])) ? $data['cfg'] : null;

if ($projetId <= 0 || $sigId <= 0) out(400, ['error'=>'Paramètres manquants']);

require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) out(500, ['error'=>'DB KO']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// check projet
$stmt = $pdo->prepare("SELECT id FROM projets WHERE id=? AND id_utilisateur=?");
$stmt->execute([$projetId, $userId]);
if (!$stmt->fetchColumn()) out(403, ['error'=>'Projet introuvable ou interdit']);

// récupère signature (doit appartenir à ce user+projet)
$stmt = $pdo->prepare("
  SELECT file_path
  FROM signatures
  WHERE id=? AND id_utilisateur=? AND projet_id=?
  LIMIT 1
");
$stmt->execute([$sigId, $userId, $projetId]);
$row = $stmt->fetch();
if (!$row) out(404, ['error'=>"Signature introuvable"]);

$cfgJson = null;
if ($cfg) {
  $x = isset($cfg['x']) ? (float)$cfg['x'] : 125.0;
  $y = isset($cfg['y']) ? (float)$cfg['y'] : 235.0;
  $w = isset($cfg['w']) ? (float)$cfg['w'] : 55.0;
  $w = max(20.0, min(100.0, $w));
  $x = max(5.0, $x);
  $y = max(5.0, $y);
  $cfgJson = json_encode(['x'=>$x,'y'=>$y,'w'=>$w], JSON_UNESCAPED_UNICODE);
}

$now = date('Y-m-d H:i:s');

// update / insert contrat
$stmt = $pdo->prepare("SELECT id FROM contrats WHERE id_utilisateur=? AND projet_id=? LIMIT 1");
$stmt->execute([$userId, $projetId]);
$c = $stmt->fetch();

if ($c) {
  $stmt = $pdo->prepare("UPDATE contrats SET signature_path=?, cfg_json=?, updated_at=? WHERE id=?");
  $stmt->execute([(string)$row['file_path'], $cfgJson, $now, (int)$c['id']]);
} else {
  $stmt = $pdo->prepare("
    INSERT INTO contrats (id_utilisateur, projet_id, signature_path, cfg_json, statut, signed_at, created_at, updated_at)
    VALUES (?, ?, ?, ?, 'brouillon', NULL, ?, ?)
  ");
  $stmt->execute([$userId, $projetId, (string)$row['file_path'], $cfgJson, $now, $now]);
}

out(200, [
  'ok'=>true,
  'message'=>"Signature active mise à jour ✅",
  'active_signature'=>(string)$row['file_path']
]);
