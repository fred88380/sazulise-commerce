<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier

if (empty($_SESSION['user_id'])) { http_response_code(403); die("Non connecté."); }
$userId = (int)$_SESSION['user_id'];

$projetId = isset($_GET['projet_id']) ? (int)$_GET['projet_id'] : 0;
if ($projetId <= 0) { http_response_code(400); die("projet_id manquant"); }

require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) die("DB KO");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// check projet
$stmt = $pdo->prepare("SELECT id FROM projets WHERE id=? AND id_utilisateur=?");
$stmt->execute([$projetId, $userId]);
if (!$stmt->fetchColumn()) { http_response_code(403); die("Projet interdit"); }

// check signature exists
$stmt = $pdo->prepare("SELECT signature_path FROM contrats WHERE id_utilisateur=? AND projet_id=? LIMIT 1");
$stmt->execute([$userId, $projetId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || empty($row['signature_path'])) {
  header("Location: signature.php?projet_id=".$projetId."&err=missing_signature");
  exit;
}

// set signed
$stmt = $pdo->prepare("UPDATE contrats SET statut='signé', signed_at=NOW(), updated_at=NOW() WHERE id_utilisateur=? AND projet_id=?");
$stmt->execute([$userId, $projetId]);

// retour profil
header("Location: ../pages/profil.php#projets");
exit;
