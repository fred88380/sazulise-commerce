<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer toutes les créations
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM creations');
    echo json_encode($stmt->fetchAll());
    exit;
}
// Ajouter une création
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO creations (titre, description, image, statut) VALUES (?, ?, ?, ?)');
    $stmt->execute([$data['titre'], $data['description'], $data['image'], $data['statut']]);
    echo json_encode(['success' => true]);
    exit;
}
?>