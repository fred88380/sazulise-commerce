<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer tous les paramètres
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM settings');
    echo json_encode($stmt->fetchAll());
    exit;
}
// Modifier un paramètre
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('REPLACE INTO settings (cle, valeur) VALUES (?, ?)');
    $stmt->execute([$data['cle'], $data['valeur']]);
    echo json_encode(['success' => true]);
    exit;
}
?>