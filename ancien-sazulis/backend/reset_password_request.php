<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
// Script backend pour générer un token, l'enregistrer et envoyer l'email de reset
require_once 'db.php';
require_once 'mail_util.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reset_msg'] = 'Email invalide.';
        header('Location: ../pages/motdepasse.php');
        exit;
    }
    // Vérifier si l'utilisateur existe
    $stmt = $pdo->prepare('SELECT id, nom FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user) {
        // Générer un token unique
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1h de validité
        // Enregistrer le token
        $pdo->prepare('INSERT INTO reset_tokens (user_id, token, expires_at) VALUES (?, ?, ?)')
            ->execute([$user['id'], $token, $expires]);
        // Préparer le lien de reset
        $reset_link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/../pages/reset.php?token=' . $token;
        // Envoi via PHPMailer (SMTP ou mail)
        sendResetMail($email, $user['nom'], $reset_link);
    }
    // Toujours afficher le même message pour la sécurité
    $_SESSION['reset_msg'] = 'Si cet email existe, un lien de réinitialisation a été envoyé.';
    header('Location: ../pages/motdepasse.php');
    exit;
}
// Redirection si accès direct
header('Location: ../pages/motdepasse.php');
exit;
