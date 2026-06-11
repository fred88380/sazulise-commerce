<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
// set_active_signature.php pour factures : définit une signature comme active
session_start();
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Non connecté.']);
  exit;
}
$userId = (int)$_SESSION['user_id'];
$factureId = isset($_POST['facture_id']) ? (int)$_POST['facture_id'] : 0;
$imgFile = isset($_POST['file']) ? basename($_POST['file']) : '';
if ($factureId <= 0 || !$imgFile) {
  http_response_code(400);
  echo json_encode(['error' => 'Paramètres manquants']);
  exit;
}
$signDir = __DIR__ . '/signatures';
$imgPath = $signDir . '/' . $imgFile;
if (!is_file($imgPath)) {
  http_response_code(404);
  echo json_encode(['error' => 'Fichier signature introuvable']);
  exit;
}
$activeFile = $signDir . '/' . $factureId . '_' . $userId . '_active.txt';
file_put_contents($activeFile, $imgFile);
echo json_encode(['success' => true]);
