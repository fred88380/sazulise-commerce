<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
// load_signature.php pour factures : charge la signature active pour une facture donnée
session_start();
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['user_id'])) {
  http_response_code(403);
  echo json_encode(['error' => 'Non connecté.']);
  exit;
}
$userId = (int)$_SESSION['user_id'];
$factureId = isset($_GET['facture_id']) ? (int)$_GET['facture_id'] : 0;
if ($factureId <= 0) {
  http_response_code(400);
  echo json_encode(['error' => 'facture_id manquant']);
  exit;
}
$signDir = __DIR__ . '/signatures';
$activeFile = $signDir . '/' . $factureId . '_' . $userId . '_active.txt';
if (!is_file($activeFile)) {
  http_response_code(404);
  echo json_encode(['error' => 'Aucune signature active']);
  exit;
}
$imgFile = trim(file_get_contents($activeFile));
$imgPath = $signDir . '/' . $imgFile;
if (!is_file($imgPath)) {
  http_response_code(404);
  echo json_encode(['error' => 'Fichier signature manquant']);
  exit;
}
header('Content-Type: image/png');
readfile($imgPath);
exit;
