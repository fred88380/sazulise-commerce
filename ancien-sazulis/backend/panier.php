<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer le panier d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id_utilisateur'])) {
    $stmt = $pdo->prepare('SELECT * FROM panier WHERE id_utilisateur = ?');
    $stmt->execute([$_GET['id_utilisateur']]);
    echo json_encode($stmt->fetchAll());
    exit;
}
// Ajouter un produit au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO panier (id_utilisateur, id_produit, quantite) VALUES (?, ?, ?)');
    $stmt->execute([$data['id_utilisateur'], $data['id_produit'], $data['quantite']]);
    echo json_encode(['success' => true]);
    exit;
}
?>