<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../protect.php';

function out(array $data, int $code = 200): void {
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

if (empty($_SESSION['user_id'])) out(['error'=>'Non connecté'], 403);
$userId = (int)$_SESSION['user_id'];

$projetId = isset($_GET['projet_id']) ? (int)$_GET['projet_id'] : 0;
if ($projetId <= 0) out(['error'=>'projet_id manquant'], 400);

require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) out(['error'=>'DB KO'], 500);

// Vérifie projet
$stmt = $pdo->prepare("SELECT id FROM projets WHERE id=? AND id_utilisateur=?");
$stmt->execute([$projetId, $userId]);
if (!$stmt->fetchColumn()) out(['error'=>'Projet interdit'], 403);

$signDir  = __DIR__ . '/signatures';
$fileName = $userId . '_' . $projetId . '.png';
$cfgFile  = $signDir . '/' . $userId . '_' . $projetId . '_cfg.json';
$fileFs   = $signDir . '/' . $fileName;

$signatureUrl = is_file($fileFs) ? 'signatures/' . $fileName : null;

$cfg = null;
if (is_file($cfgFile)) {
    $tmp = json_decode(file_get_contents($cfgFile), true);
    if (is_array($tmp)) $cfg = $tmp;
}

out([
    'ok'            => true,
    'signature_url' => $signatureUrl,
    'cfg'           => $cfg,
    'statut'        => $signatureUrl ? 'brouillon' : null
]);