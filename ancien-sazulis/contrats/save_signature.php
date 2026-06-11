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

if (empty($_SESSION['user_id'])) out(['error' => 'Non connecté.'], 403);
$userId = (int)$_SESSION['user_id'];

require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) out(['error'=>'DB KO'], 500);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$raw  = file_get_contents('php://input');
$data = json_decode((string)$raw, true);
if (!is_array($data)) out(['error' => 'JSON invalide'], 400);

$projetId = isset($data['projet_id']) ? (int)$data['projet_id'] : 0;
if ($projetId <= 0) out(['error' => 'projet_id manquant'], 400);

// Vérifie que le projet appartient au user
$stmt = $pdo->prepare("SELECT id FROM projets WHERE id=? AND id_utilisateur=?");
$stmt->execute([$projetId, $userId]);
if (!$stmt->fetchColumn()) out(['error' => 'Projet introuvable ou interdit.'], 403);

// Dossier signatures (dans /contrats/signatures/)
$signDir = __DIR__ . '/signatures';
if (!is_dir($signDir)) @mkdir($signDir, 0777, true);

$fileName       = $userId . '_' . $projetId . '.png';
$fileFs         = $signDir . '/' . $fileName;
$cfgFile        = $signDir . '/' . $userId . '_' . $projetId . '_cfg.json';
$signaturePathWeb = 'signatures/' . $fileName;

// === 1) FINALISER (bouton Valider) ===
if (!empty($data['finalize'])) {

    // Sauvegarde l'image si envoyée
    if (!empty($data['image']) && is_string($data['image'])) {
        if (preg_match('#^data:image/png;base64,#', $data['image'])) {
            $b64 = substr($data['image'], strpos($data['image'], ',') + 1);
            $bin = base64_decode($b64, true);
            if ($bin !== false) file_put_contents($fileFs, $bin);
        }
    }

    // Vérifie qu'une signature existe
    if (!is_file($fileFs)) {
        out(['error' => 'Aucune signature enregistrée. Dessine et enregistre ta signature d\'abord.'], 400);
    }

    // Sauvegarde le cfg si envoyé
    if (!empty($data['cfg']) && is_array($data['cfg'])) {
        $x = max(0.0, min(210.0, (float)($data['cfg']['x'] ?? 125)));
        $y = max(0.0, min(297.0, (float)($data['cfg']['y'] ?? 235)));
        $w = max(20.0, min(100.0, (float)($data['cfg']['w'] ?? 55)));
        file_put_contents($cfgFile, json_encode(['x'=>$x,'y'=>$y,'w'=>$w]));
    }

    // Marque contrat_signe = 1 dans projets
    $pdo->prepare("UPDATE projets SET contrat_signe=1 WHERE id=? AND id_utilisateur=?")->execute([$projetId, $userId]);

    out([
        'ok'           => true,
        'message'      => '✅ Contrat signé et enregistré !',
        'redirect_url' => '../pages/profil.php#projets'
    ]);
}

// === 2) SAUVEGARDER IMAGE ===
if (!empty($data['image']) && is_string($data['image'])) {
    if (!preg_match('#^data:image/png;base64,#', $data['image'])) {
        out(['error' => 'Format image invalide (PNG base64 attendu)'], 400);
    }

    $b64 = substr($data['image'], strpos($data['image'], ',') + 1);
    $bin = base64_decode($b64, true);
    if ($bin === false) out(['error'=>'Base64 invalide'], 400);

    if (file_put_contents($fileFs, $bin) === false) {
        out(['error' => 'Impossible d\'écrire le fichier signature. Vérifie les droits du dossier /contrats/signatures'], 500);
    }

    // Sauvegarde cfg en même temps si présent
    if (!empty($data['cfg']) && is_array($data['cfg'])) {
        $x = max(0.0, min(210.0, (float)($data['cfg']['x'] ?? 125)));
        $y = max(0.0, min(297.0, (float)($data['cfg']['y'] ?? 235)));
        $w = max(20.0, min(100.0, (float)($data['cfg']['w'] ?? 55)));
        file_put_contents($cfgFile, json_encode(['x'=>$x,'y'=>$y,'w'=>$w]));
    }

    out([
        'ok'               => true,
        'message'          => '✅ Signature enregistrée',
        'active_signature' => $signaturePathWeb,
    ]);
}

// === 3) SAUVEGARDER CFG SEULEMENT ===
if (!empty($data['cfg']) && is_array($data['cfg'])) {
    $x = max(0.0, min(210.0, (float)($data['cfg']['x'] ?? 125)));
    $y = max(0.0, min(297.0, (float)($data['cfg']['y'] ?? 235)));
    $w = max(20.0, min(100.0, (float)($data['cfg']['w'] ?? 55)));
    file_put_contents($cfgFile, json_encode(['x'=>$x,'y'=>$y,'w'=>$w]));

    out(['ok' => true, 'message' => '✅ Placement sauvegardé']);
}

out(['error' => 'Aucune action reconnue (image / cfg / finalize)'], 400);