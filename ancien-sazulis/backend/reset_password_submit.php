<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
// Script backend pour valider le token, mettre à jour le mot de passe hashé et supprimer le token
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['reset_token'], $_POST['password'], $_POST['password2'])) {
    $token = $_SESSION['reset_token'];
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    if ($password !== $password2 || strlen($password) < 6) {
        $_SESSION['reset_msg'] = 'Les mots de passe ne correspondent pas ou sont trop courts.';
        header('Location: ../pages/reset.php?token=' . urlencode($token));
        exit;
    }
    // Vérifier le token
    $stmt = $pdo->prepare('SELECT * FROM reset_tokens WHERE token = ? AND expires_at > NOW()');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if ($row) {
        // Mettre à jour le mot de passe hashé
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ? WHERE id = ?')->execute([$hash, $row['user_id']]);
        // Supprimer le token
        $pdo->prepare('DELETE FROM reset_tokens WHERE token = ?')->execute([$token]);
        unset($_SESSION['reset_token']);
        $_SESSION['reset_success'] = true;
        header('Location: ../pages/reset.php');
        exit;
    } else {
        $_SESSION['reset_msg'] = 'Lien invalide ou expiré.';
        header('Location: ../pages/reset.php');
        exit;
    }
}
// Redirection si accès direct
header('Location: ../pages/motdepasse.php');
exit;
