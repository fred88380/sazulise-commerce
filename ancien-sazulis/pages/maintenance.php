<?php
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
// Détection de la langue (URL > cookie > défaut)
session_start();
$lang = 'fr';
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    setcookie('lang', $lang, time() + (3600 * 24 * 30), "/");
} elseif (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'];
}
$langs = include __DIR__ . '/../lang/' . $lang . '.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= $langs['maintenance_title'] ?? 'Maintenance' ?> | Sazulis</title>
    <meta name="description" content="Sazulis est en maintenance. Nous travaillons à l'amélioration de nos services pour mieux vous servir. Merci de votre patience." />
    <meta name="keywords" content="maintenance, sazulis, indisponible, amélioration, service, web" />
    <link rel="icon" type="image/x-icon" href="../assets/img/sazulis-ico.ico">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        .maintenance-bg {
            min-height: 100vh;
            width: 100vw;
            position: fixed;
            top: 0; left: 0;
            background: url('../assets/img/officiel-maintenance.png') center/cover no-repeat scroll;
            z-index: -1;
        }
        .maintenance-message {
            position: relative;
            z-index: 1;
            max-width: 600px;
            margin: 0 auto;
            top: 30vh;
            background: rgba(255,255,255,0.95);
            border-radius: 32px;
            box-shadow: 0 8px 32px #ffd70055, 0 2px 8px #0002;
            padding: 2.5em 2em 2em 2em;
            text-align: center;
            border: 2px solid #ffe9c6;
        }
        .maintenance-message h1 {
            color: #d4af37;
            font-size: 2.2em;
            margin-bottom: 0.5em;
        }
        .maintenance-message p {
            color: #333;
            font-size: 1.2em;
            margin-bottom: 1.2em;
        }
        .maintenance-message .contact {
            color: #b8860b;
            font-size: 1.1em;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="maintenance-bg"></div>
    <div class="maintenance-message">
        <h1><?= $langs['maintenance_h1'] ?? 'Désolé pour ce problème technique' ?></h1>
        <p><?= $langs['maintenance_message'] ?? 'Notre site est actuellement en maintenance.<br>Nous faisons tout notre possible pour rétablir le service dans les plus brefs délais.' ?></p>
        <p class="contact">
            <?= $langs['maintenance_contact'] ?? 'Pour toute demande urgente, contactez le service clients :' ?><br>
            <a href="mailto:contact@sazulis.fr">contact@sazulis.fr</a>
        </p>
    </div>
</body>
</html>
