<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
require_once 'db.php';
// Récupérer tous les utilisateurs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query('SELECT * FROM utilisateurs');
    echo json_encode($stmt->fetchAll());
    exit;
}
// Ajouter un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    try {
        $stmt = $pdo->prepare('INSERT INTO utilisateurs (nom, email, mot_de_passe) VALUES (?, ?, ?)');
        $stmt->execute([$data['nom'], $data['email'], password_hash($data['mot_de_passe'], PASSWORD_DEFAULT)]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Violation de contrainte UNIQUE (email déjà utilisé)
            http_response_code(409);
            echo json_encode(['error' => 'email_exists']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'db_error', 'message' => $e->getMessage()]);
        }
    }
    exit;
}
?>