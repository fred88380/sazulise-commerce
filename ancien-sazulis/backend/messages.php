<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer tous les messages
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM messages');
    echo json_encode($stmt->fetchAll());
    exit;
}
// Ajouter un message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO messages (nom, email, sujet, message) VALUES (?, ?, ?, ?)');
    $stmt->execute([$data['nom'], $data['email'], $data['sujet'], $data['message']]);
    echo json_encode(['success' => true]);
    exit;
}
?>