<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
// save_signature.php pour factures : enregistre la signature et la rend active
session_start();
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Non connecté.']);
  exit;
}
$userId = (int)$_SESSION['user_id'];
require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
  http_response_code(500);
  echo json_encode(['error'=>'DB KO']);
  exit;
}
$raw = file_get_contents('php://input');
$data = json_decode((string)$raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['error' => 'JSON invalide']);
  exit;
}
$factureId = isset($data['facture_id']) ? (int)$data['facture_id'] : 0;
if ($factureId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'facture_id manquant']);
  exit;
}
// Vérifie que la facture appartient à l'utilisateur (via la commande)
$stmt = $pdo->prepare("SELECT f.id FROM factures f INNER JOIN commandes c ON f.id_commande = c.id WHERE f.id=? AND c.id_utilisateur=?");
$stmt->execute([$factureId, $userId]);
if (!$stmt->fetchColumn()) {
  http_response_code(403);
  echo json_encode(['error' => 'Facture introuvable ou interdite.']);
  exit;
}
$signDir = __DIR__ . '/signatures';
if (!is_dir($signDir)) @mkdir($signDir, 0777, true);
if (!empty($data['image']) && is_string($data['image'])) {
  $img = $data['image'];
  if (!preg_match('#^data:image/png;base64,#', $img)) {
    http_response_code(400);
    echo json_encode(['error' => 'Format image invalide (PNG base64 attendu)']);
    exit;
  }
  $imgData = base64_decode(str_replace('data:image/png;base64,', '', $img));
  if (!$imgData) {
    http_response_code(400);
    echo json_encode(['error' => 'Décodage base64 échoué']);
    exit;
  }
  $file = $signDir . '/' . $factureId . '_' . $userId . '.png';
  file_put_contents($file, $imgData);
  // Marquer cette signature comme active
  $activeFile = $signDir . '/' . $factureId . '_' . $userId . '_active.txt';
  file_put_contents($activeFile, basename($file));
  echo json_encode(['success' => true, 'file' => basename($file)]);
  exit;
}
echo json_encode(['error' => 'Image manquante']);
