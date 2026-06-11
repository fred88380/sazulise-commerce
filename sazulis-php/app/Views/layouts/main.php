<?php
/** @var string $contentView */
$metaTitle = $metaTitle ?? 'Sazulis';
$config = require __DIR__ . '/../../../config/app.php';
$runtimeBasePath = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
$runtimeBasePath = ($runtimeBasePath === '.' || $runtimeBasePath === '/') ? '' : rtrim($runtimeBasePath, '/');
$runtimeBasePath = (string) preg_replace('#/(?:public|index\.php)$#', '', $runtimeBasePath);
$runtimeBasePath = (string) preg_replace('#/app/Views(?:/.*)?$#', '', $runtimeBasePath);
$runtimeBasePath = rtrim($runtimeBasePath, '/');
$configuredBasePath = rtrim((string) (parse_url((string) ($config['base_url'] ?? ''), PHP_URL_PATH) ?: ''), '/');
$basePath = $runtimeBasePath !== '' ? $runtimeBasePath : $configuredBasePath;
$assetsBasePath = $basePath . '/public';
$user = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
$role = (string) ($user['role'] ?? 'guest');

if (!defined('SAZULIS_IN_LAYOUT')) {
    define('SAZULIS_IN_LAYOUT', true);
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <meta name="description" content="Sazulis - E-commerce nouvelle generation en PHP">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars($assetsBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
</head>
<body>
<div class="noise"></div>
<div class="topbar-contact">
    <span class="topbar-item">SAZULIS Developpeur web</span>
    <span class="topbar-item"><a href="tel:0698766780">06 98 76 67 80</a></span>
    <span class="topbar-item">SIRET : 752 628 040 00020</span>
    <span class="topbar-item"><a href="mailto:contact@sazulis.fr">contact@sazulis.fr</a></span>
    <span class="topbar-item">Vosges &amp; France entiere</span>
</div>
<header class="topbar">
    <div class="topbar-logo">
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/" class="brand">
            <img src="<?= htmlspecialchars($assetsBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/sazulis-logo1.png" alt="Logo Sazulis">
        </a>
    </div>
    <nav class="topbar-nav">
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/">Accueil</a>
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop">Boutique</a>
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/contact">Contact</a>
        <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/audit">Audit</a>
        <?php if ($role === 'admin' || $role === 'client'): ?>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/checkout">Panier (<span id="cart-count">0</span>)</a>
        <?php endif; ?>

        <?php if ($role === 'admin'): ?>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin">Administration</a>
            <form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/logout" class="topbar-inline-form">
                <button class="topbar-nav-btn is-danger" type="submit">Deconnexion</button>
            </form>
        <?php elseif ($role === 'client'): ?>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile">Profil</a>
            <form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/logout" class="topbar-inline-form">
                <button class="topbar-nav-btn is-danger" type="submit">Deconnexion</button>
            </form>
        <?php else: ?>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">Connexion</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <?php require $contentView; ?>
</main>

<footer class="footer-modern">
    <div class="footer-top">
        <div class="footer-brand-block">
            <img src="<?= htmlspecialchars($assetsBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/sazulis-logo1.png" alt="Logo Sazulis" class="footer-logo">
            <div class="footer-contact">
                <span>SAZULIS Developpeur web</span>
                <a href="tel:0698766780">06 98 76 67 80</a>
                <a href="mailto:contact@sazulis.fr">contact@sazulis.fr</a>
            </div>
        </div>
        <nav class="footer-links">
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/mentions-legales">Mentions legales</a>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/apropos">A propos</a>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/cgu">CGU</a>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/cgv">CGV</a>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/paiement-securise">Paiement securise</a>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/conditions-livraison">Conditions de livraison</a>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/partenaire">Partenaires</a>
            <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/creations">Creations</a>
        </nav>
    </div>
    <p class="footer-bottom">Concu par Sazulis © 2026</p>
</footer>

<script>window.SAZULIS_BASE = '<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>';</script>
<script>window.SAZULIS_USER = <?= $user ? json_encode($user, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : 'null' ?>;</script>
<script src="<?= htmlspecialchars($assetsBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/js/app.js" defer></script>
</body>
</html>
