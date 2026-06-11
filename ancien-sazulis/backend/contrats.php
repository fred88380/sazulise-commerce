<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer tous les contrats
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM contrats');
    echo json_encode($stmt->fetchAll());
    exit;
}
// Ajouter un contrat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO contrats (id_utilisateur, titre, contenu, date_signature, statut) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$data['id_utilisateur'], $data['titre'], $data['contenu'], $data['date_signature'], $data['statut']]);
    echo json_encode(['success' => true]);
    exit;
}
?>