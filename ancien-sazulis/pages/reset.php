<?php
// pages/motdepasse.php (reset via token)

// Session safe (pas de double session_start)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security_headers.php';
require_once __DIR__ . '/../backend/db.php';

// Lang (GET > cookie > défaut)
$lang = 'fr';
$allowed = ['fr','en','es','de','it'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
    $lang = $_GET['lang'];
} elseif (isset($_COOKIE['siteLang']) && in_array($_COOKIE['siteLang'], $allowed, true)) {
    $lang = $_COOKIE['siteLang'];
}

// (optionnel) cookie langue
if (!isset($_COOKIE['siteLang']) || $_COOKIE['siteLang'] !== $lang) {
    setcookie('siteLang', $lang, time() + 3600*24*30, '/');
}

$langs = [
    'fr' => [
        'title' => 'Réinitialiser le mot de passe',
        'desc' => 'Choisissez un nouveau mot de passe.',
        'password' => 'Nouveau mot de passe',
        'confirm' => 'Confirmer le mot de passe',
        'submit' => 'Réinitialiser',
        'invalid' => 'Lien invalide ou expiré.',
        'success' => 'Votre mot de passe a été réinitialisé avec succès.',
        'error' => 'Erreur lors de la réinitialisation.'
    ],
    'en' => [
        'title' => 'Reset password',
        'desc' => 'Choose a new password.',
        'password' => 'New password',
        'confirm' => 'Confirm password',
        'submit' => 'Reset',
        'invalid' => 'Invalid or expired link.',
        'success' => 'Your password has been reset successfully.',
        'error' => 'Error during reset.'
    ]
];

if (!isset($langs[$lang])) $lang = 'fr';

$msg = '';
$show_form = false;

if (isset($_GET['token'])) {
    $token = (string)$_GET['token'];

    $stmt = $pdo->prepare('SELECT * FROM reset_tokens WHERE token = ? AND expires_at > NOW()');
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    if ($row) {
        $show_form = true;
        $_SESSION['reset_token'] = $token;
    } else {
        $msg = $langs[$lang]['invalid'];
    }
} elseif (!empty($_SESSION['reset_success'])) {
    $msg = $langs[$lang]['success'];
    unset($_SESSION['reset_success']);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <?php include __DIR__ . '/../head.php'; ?>
    <title><?= htmlspecialchars($langs[$lang]['title']) ?> | Sazulis</title>
    <meta name="description" content="Réinitialisez votre mot de passe Sazulis en toute sécurité grâce à ce formulaire dédié." />
    <meta name="keywords" content="réinitialisation, mot de passe, reset, sazulis, sécurité, client, accès" />
    <style>
        body { background: #f8f6f4; }
        .reset-container { max-width: 400px; margin: 4em auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 16px #d2691e22; padding: 2.5em 2em; }
        .reset-container h1 { color: #d2691e; font-size: 1.5em; margin-bottom: 1em; }
        .reset-container form { display: flex; flex-direction: column; gap: 1.2em; }
        .reset-container input[type=password] { border-radius: 8px; border: 1px solid #d2691e33; padding: 0.8em 1em; font-size: 1em; }
        .reset-container button { background: #d2691e; color: #fff; border: none; border-radius: 8px; padding: 0.7em 1.5em; font-size: 1.1em; font-weight: 700; cursor: pointer; }
        .reset-container button:hover { background: #a04d13; }
        .reset-container .msg { margin-top: 1em; color: #d2691e; font-weight: 600; }
    </style>
</head>
<body>
<div class="reset-container">
    <h1><?= htmlspecialchars($langs[$lang]['title']) ?></h1>
    <p><?= htmlspecialchars($langs[$lang]['desc']) ?></p>

    <?php if($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <?php if($show_form): ?>
        <form method="post" action="../backend/reset_password_submit.php">
            <input type="password" name="password" placeholder="<?= htmlspecialchars($langs[$lang]['password']) ?>" required minlength="6">
            <input type="password" name="password2" placeholder="<?= htmlspecialchars($langs[$lang]['confirm']) ?>" required minlength="6">
            <button type="submit"><?= htmlspecialchars($langs[$lang]['submit']) ?></button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
