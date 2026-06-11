<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer toutes les statistiques
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM statistiques');
    echo json_encode($stmt->fetchAll());
    exit;
}
// Ajouter une statistique
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO statistiques (type, valeur) VALUES (?, ?)');
    $stmt->execute([$data['type'], $data['valeur']]);
    echo json_encode(['success' => true]);
    exit;
}
?>