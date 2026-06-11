<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer toutes les factures
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM factures');
    echo json_encode($stmt->fetchAll());
    exit;
}
// Ajouter une facture
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare('INSERT INTO factures (id_commande, numero, montant, statut) VALUES (?, ?, ?, ?)');
    $stmt->execute([$data['id_commande'], $data['numero'], $data['montant'], $data['statut']]);
    echo json_encode(['success' => true]);
    exit;
}
?>