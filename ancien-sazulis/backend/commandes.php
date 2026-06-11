<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer toutes les commandes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM commandes');
    echo json_encode($stmt->fetchAll());
    exit;
}
// Ajouter une commande
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO commandes (id_utilisateur, total, statut) VALUES (?, ?, ?)');
    $stmt->execute([$data['id_utilisateur'], $data['total'], $data['statut']]);
    echo json_encode(['success' => true]);
    exit;
}
?>