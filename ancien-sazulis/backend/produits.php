<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer tous les produits
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM produits');
    echo json_encode($stmt->fetchAll());
    exit;
}
// Ajouter un produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO produits (nom, description, prix, categorie, image) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$data['nom'], $data['description'], $data['prix'], $data['categorie'], $data['image']]);
    echo json_encode(['success' => true]);
    exit;
}
?>