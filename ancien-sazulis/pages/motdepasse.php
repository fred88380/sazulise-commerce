<?php
// pages/motdepasse.php
// Page de demande de réinitialisation de mot de passe

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../security_headers.php';

$lang = 'fr';
$allowed = ['fr','en','es','de','it'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $allowed, true)) {
    $lang = $_GET['lang'];
} elseif (isset($_COOKIE['siteLang']) && in_array($_COOKIE['siteLang'], $allowed, true)) {
    $lang = $_COOKIE['siteLang'];
}

$langs = [
    'fr' => [
        'title' => 'Mot de passe oublié',
        'desc' => 'Entrez votre email pour recevoir un lien de réinitialisation.',
        'email' => 'Votre email',
        'submit' => 'Envoyer le lien',
        'success' => 'Si cet email existe, un lien de réinitialisation a été envoyé.',
        'error' => 'Veuillez entrer un email valide.'
    ],
    'en' => [
        'title' => 'Forgot password',
        'desc' => 'Enter your email to receive a reset link.',
        'email' => 'Your email',
        'submit' => 'Send link',
        'success' => 'If this email exists, a reset link has been sent.',
        'error' => 'Please enter a valid email.'
    ]
];

if (!isset($langs[$lang])) $lang = 'fr';

$msg = '';
if (!empty($_SESSION['reset_msg'])) {
    $msg = (string)$_SESSION['reset_msg'];
    unset($_SESSION['reset_msg']);
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <?php include __DIR__ . '/../head.php'; ?>
    <title><?= htmlspecialchars($langs[$lang]['title']) ?> | Sazulis</title>
    <meta name="description" content="Page de réinitialisation du mot de passe Sazulis. Recevez un lien sécurisé pour réinitialiser votre accès client." />
    <meta name="keywords" content="mot de passe, réinitialisation, oublié, sazulis, sécurité, client, accès" />
    <style>
        body { background: #f8f6f4; }
        .reset-container { max-width: 400px; margin: 4em auto; background: #fff; border-radius: 18px; box-shadow: 0 4px 16px #d2691e22; padding: 2.5em 2em; }
        .reset-container h1 { color: #d2691e; font-size: 1.5em; margin-bottom: 1em; }
        .reset-container form { display: flex; flex-direction: column; gap: 1.2em; }
        .reset-container input[type=email] { border-radius: 8px; border: 1px solid #d2691e33; padding: 0.8em 1em; font-size: 1em; }
        .reset-container button { background: #d2691e; color: #fff; border: none; border-radius: 8px; padding: 0.7em 1.5em; font-size: 1.1em; font-weight: 700; cursor: pointer; }
        .reset-container button:hover { background: #a04d13; }
        .reset-container .msg { margin-top: 1em; color: #d2691e; font-weight: 600; }
    </style>
</head>
<body style="min-height:100vh;display:flex;flex-direction:column;justify-content:flex-start;background:url('../assets/img/unique.png') center/cover no-repeat fixed;">
<?php include '../navbar.php'; ?>
<div style="flex:1;display:flex;align-items:center;justify-content:center;min-height:80vh;">
<div class="reset-container">
    <h1><?= htmlspecialchars($langs[$lang]['title']) ?></h1>
    <p><?= htmlspecialchars($langs[$lang]['desc']) ?></p>

    <form method="post" action="../backend/reset_password_request.php">
        <input type="email" name="email" placeholder="<?= htmlspecialchars($langs[$lang]['email']) ?>" required>
        <button type="submit"><?= htmlspecialchars($langs[$lang]['submit']) ?></button>
    </form>

    <?php if ($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
</div>
</div>
<?php include '../footer.php'; ?>
</body>
</html>
