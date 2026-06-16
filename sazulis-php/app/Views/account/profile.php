<?php
$user = isset($user) && is_array($user) ? $user : null;
$orders = isset($orders) && is_array($orders) ? $orders : [];
$basePath = isset($basePath) ? (string) $basePath : '';

$customerName = (string) ($user['name'] ?? 'Client');
$customerEmail = (string) ($user['email'] ?? '');
$twofaEnabled = isset($twofaEnabled) ? (bool) $twofaEnabled : ((int) ($user['totp_enabled'] ?? 0) === 1);
$profileNotice = isset($profileNotice) ? (string) $profileNotice : '';
$profileError = isset($profileError) ? (string) $profileError : '';
$csrfToken = isset($csrfToken) ? (string) $csrfToken : (string) ($_SESSION['__csrf_token'] ?? '');
$orderCount = count($orders);
$totalSpent = 0.0;
$latestOrderDate = null;

$pending = 0;
$inProgress = 0;
$done = 0;
$cancelled = 0;

foreach ($orders as $order) {
    $totalSpent += (float) ($order['total'] ?? 0);

    if ($latestOrderDate === null && !empty($order['created_at'])) {
        $latestOrderDate = (string) $order['created_at'];
    }

    $status = strtolower((string) ($order['status'] ?? 'pending'));
    if ($status === 'cancelled') {
        $cancelled++;
    } elseif ($status === 'paid' || $status === 'completed') {
        $done++;
    } elseif ($status === 'processing' || $status === 'shipped') {
        $inProgress++;
    } else {
        $pending++;
    }
}

$statusLabel = static function (string $status): string {
    return match (strtolower($status)) {
        'paid' => 'Payee',
        'processing' => 'En traitement',
        'shipped' => 'Expediee',
        'completed' => 'Terminee',
        'cancelled' => 'Annulee',
        default => 'En attente',
    };
};

$memberSince = !empty($user['created_at']) ? (string) $user['created_at'] : 'Compte actif';
$isInLayout = defined('SAZULIS_IN_LAYOUT') && SAZULIS_IN_LAYOUT;
$assetsBasePath = '';
$layoutUser = null;
$layoutRole = 'guest';
$latestOrder = !empty($orders) && is_array($orders) ? $orders[0] : null;
$latestOrderId = $latestOrder ? (string) ($latestOrder['id'] ?? '') : '';
$appConfig = require __DIR__ . '/../../../config/app.php';
$configuredBasePath = rtrim((string) (parse_url((string) ($appConfig['base_url'] ?? ''), PHP_URL_PATH) ?: ''), '/');

if (!$isInLayout) {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  if ($csrfToken === '') {
    $csrfToken = (string) ($_SESSION['__csrf_token'] ?? '');
    if ($csrfToken === '') {
      $csrfToken = bin2hex(random_bytes(32));
      $_SESSION['__csrf_token'] = $csrfToken;
    }
  }

  $runtimeBasePath = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')));
  $runtimeBasePath = ($runtimeBasePath === '.' || $runtimeBasePath === '/') ? '' : rtrim($runtimeBasePath, '/');
  $runtimeBasePath = (string) preg_replace('#/(?:public|index\.php)$#', '', $runtimeBasePath);
  $runtimeBasePath = (string) preg_replace('#/app/Views(?:/.*)?$#', '', $runtimeBasePath);
  $runtimeBasePath = rtrim($runtimeBasePath, '/');

  if ($basePath === '') {
    $basePath = $runtimeBasePath !== '' ? $runtimeBasePath : $configuredBasePath;
  }

  $assetsBasePath = $basePath . '/public';
  $layoutUser = isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
  $layoutRole = (string) ($layoutUser['role'] ?? 'guest');
  ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil client - Sazulis</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars($assetsBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
</head>
<body class="profile-fallback">
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
    <?php if ($layoutRole === 'admin' || $layoutRole === 'client'): ?>
      <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/checkout">Panier (<span id="cart-count">0</span>)</a>
    <?php endif; ?>

    <?php if ($layoutRole === 'admin'): ?>
      <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/admin">Administration</a>
      <form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/logout" class="topbar-inline-form">
        <input type="hidden" name="__csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button class="topbar-nav-btn is-danger" type="submit">Deconnexion</button>
      </form>
    <?php elseif ($layoutRole === 'client'): ?>
      <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile">Profil</a>
      <span class="topbar-2fa-badge <?= $twofaEnabled ? 'is-on' : 'is-off' ?>">
        <?= $twofaEnabled ? '2FA activee' : '2FA inactive' ?>
      </span>
      <form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/logout" class="topbar-inline-form">
        <input type="hidden" name="__csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <button class="topbar-nav-btn is-danger" type="submit">Deconnexion</button>
      </form>
    <?php else: ?>
      <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">Connexion</a>
    <?php endif; ?>
  </nav>
</header>
<main>
  <?php
}
?>

<style>
.profile-fallback {
  margin: 0;
  color: #eff8ff;
  background: radial-gradient(circle at 12% 0%, #1c3c58 0%, transparent 36%),
              radial-gradient(circle at 90% -10%, #61361f 0%, transparent 32%),
              radial-gradient(circle at 80% 100%, #1b3550 0%, transparent 25%),
              linear-gradient(130deg, #071019 0%, #04070c 100%);
  font-family: 'Manrope', sans-serif;
  min-height: 100vh;
}

.profile-fallback main {
  width: min(1180px, 92vw);
  margin: 36px auto 46px;
}

.profile-fallback .noise {
  position: fixed;
  inset: 0;
  pointer-events: none;
  background-image: radial-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px);
  background-size: 4px 4px;
}

.profile-fallback .topbar-contact {
  background: #1a2347;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.2rem;
  padding: 0.55rem 1rem;
  font-size: 0.92rem;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
  flex-wrap: wrap;
}

.profile-fallback .topbar-item { white-space: nowrap; }

.profile-fallback .topbar-item a {
  color: #fff;
  text-decoration: underline;
}

.profile-fallback .topbar {
  position: sticky;
  top: 0;
  z-index: 30;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 24px;
  padding: 10px 18px;
  background: #fff;
  border-bottom: 1px solid rgba(26, 35, 71, 0.12);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.profile-fallback .brand {
  display: inline-flex;
  align-items: center;
  text-decoration: none;
}

.profile-fallback .topbar-logo img {
  height: 48px;
  width: auto;
  display: block;
}

.profile-fallback .topbar-nav {
  display: flex;
  gap: 22px;
  align-items: center;
  flex-wrap: wrap;
  justify-content: center;
}

.profile-fallback .topbar-nav a {
  color: #1a2347;
  text-decoration: none;
  transition: color 0.2s ease;
  font-weight: 600;
}

.profile-fallback .topbar-nav a:hover,
.profile-fallback .topbar-nav-btn:hover {
  color: #32488e;
}

.profile-fallback .topbar-inline-form { display: inline; }

.profile-fallback .topbar-nav-btn {
  border: 0;
  background: transparent;
  color: #1a2347;
  font-weight: 600;
  padding: 0;
  font-size: 1rem;
  cursor: pointer;
}

.profile-fallback .topbar-nav-btn.is-danger { color: #d9534f; }

.profile-fallback .topbar-2fa-badge {
  border-radius: 999px;
  padding: 5px 10px;
  font-size: 0.8rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  border: 1px solid;
}

.profile-fallback .topbar-2fa-badge.is-on {
  color: #0f5f3e;
  border-color: rgba(32, 173, 114, 0.35);
  background: rgba(32, 173, 114, 0.15);
}

.profile-fallback .topbar-2fa-badge.is-off {
  color: #8a5a18;
  border-color: rgba(210, 160, 70, 0.45);
  background: rgba(210, 160, 70, 0.16);
}

.profile-fallback .footer-modern {
  margin-top: auto;
  border-top: 1px solid rgba(255, 255, 255, 0.14);
  background: linear-gradient(180deg, rgba(8, 16, 24, 0.92), rgba(4, 10, 16, 0.96));
  padding: 26px 20px;
}

.profile-fallback .footer-top {
  width: min(1180px, 92vw);
  margin: 0 auto;
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 18px;
  align-items: center;
}

.profile-fallback .footer-brand-block {
  display: flex;
  align-items: center;
  gap: 14px;
}

.profile-fallback .footer-logo {
  width: 96px;
  height: auto;
}

.profile-fallback .footer-contact {
  display: grid;
  gap: 4px;
}

.profile-fallback .footer-contact span,
.profile-fallback .footer-contact a {
  color: #a9bfd5;
  text-decoration: none;
  font-size: 0.92rem;
}

.profile-fallback .footer-links {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 14px;
  justify-content: flex-end;
}

.profile-fallback .footer-links a {
  color: #eff8ff;
  text-decoration: none;
  font-size: 0.9rem;
  border: 1px solid rgba(255, 255, 255, 0.14);
  border-radius: 999px;
  padding: 7px 12px;
  background: rgba(255, 255, 255, 0.03);
}

.profile-fallback .footer-bottom {
  text-align: center;
  color: #a9bfd5;
  margin: 14px 0 0;
}

.profile-page {
  --p-text: #eff8ff;
  --p-muted: #a9bfd5;
  --p-accent: #ff7a45;
  --p-accent-2: #1dd9b7;
  --p-line: rgba(255, 255, 255, 0.14);
  color: var(--p-text);
  display: grid;
  gap: 18px;
}

.profile-card {
  background: linear-gradient(180deg, rgba(13, 25, 39, 0.95), rgba(10, 19, 29, 0.78));
  border: 1px solid var(--p-line);
  border-radius: 20px;
  padding: 22px;
  box-shadow: 0 22px 52px rgba(0, 0, 0, 0.34);
}

.profile-page h1,
.profile-page h2,
.profile-page h3 {
  margin: 0 0 10px;
}

.profile-page h1 {
  font-size: clamp(2rem, 5vw, 3.4rem);
  line-height: 0.98;
  letter-spacing: 0.4px;
  font-family: 'Bebas Neue', sans-serif;
}

.profile-page p {
  color: var(--p-muted);
  margin: 0;
}

.profile-hero {
  display: grid;
  grid-template-columns: 1.25fr 0.95fr;
  gap: 20px;
}

.profile-tag {
  color: var(--p-accent-2);
  text-transform: uppercase;
  letter-spacing: 0.1em;
  font-size: 0.78rem;
  margin-bottom: 10px;
}

.profile-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 18px;
}

.profile-order-actions {
  margin-top: 14px;
}

.profile-signature-box {
  margin-top: 14px;
  border: 1px solid var(--p-line);
  border-radius: 14px;
  padding: 12px;
  background: rgba(0, 0, 0, 0.22);
}

.profile-signature-box p {
  margin-bottom: 8px;
}

.profile-signature-canvas {
  width: 100%;
  height: 140px;
  background: #fff;
  border-radius: 10px;
  cursor: crosshair;
  display: block;
}

.profile-signature-preview {
  max-width: 200px;
  background: #fff;
  border-radius: 10px;
  padding: 6px;
  margin-top: 8px;
}

.profile-signature-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-top: 10px;
}

.profile-documents {
  display: grid;
  gap: 12px;
}

.profile-document-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.profile-document-note {
  color: var(--p-muted);
  font-size: 0.94rem;
}

.profile-btn {
  border: 0;
  cursor: pointer;
  border-radius: 999px;
  padding: 12px 18px;
  font-weight: 700;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 0.18s ease, filter 0.18s ease, box-shadow 0.18s ease;
}

.profile-btn:hover {
  transform: translateY(-1px);
  filter: brightness(1.04);
}

.profile-btn-primary {
  background: linear-gradient(120deg, var(--p-accent), #f18f2a);
  color: #1a0e03;
  box-shadow: 0 10px 22px rgba(255, 111, 63, 0.28);
}

.profile-btn-ghost {
  background: transparent;
  color: var(--p-text);
  border: 1px solid var(--p-line);
}

.profile-kpis {
  display: grid;
  gap: 10px;
}

.profile-kpi {
  padding: 16px;
  border-radius: 14px;
  border: 1px solid var(--p-line);
  background: rgba(0, 0, 0, 0.2);
}

.profile-kpi strong {
  display: block;
  font-size: 1.35rem;
}

.profile-quick ul {
  list-style: none;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  padding: 0;
  margin: 0;
}

.profile-quick li {
  border: 1px solid var(--p-line);
  border-radius: 12px;
  padding: 10px;
  color: var(--p-text);
  background: rgba(0, 0, 0, 0.18);
}

.profile-split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
}

.profile-account-lines {
  display: grid;
  gap: 8px;
}

.profile-account-lines p {
  border-bottom: 1px solid var(--p-line);
  padding-bottom: 8px;
}

.profile-account-lines p:last-child {
  border-bottom: 0;
  padding-bottom: 0;
}

.profile-2fa-card {
  border-color: rgba(255, 207, 64, 0.36);
}

.profile-2fa-status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-radius: 999px;
  border: 1px solid rgba(255, 207, 64, 0.42);
  background: rgba(255, 207, 64, 0.12);
  color: #ffe9a8;
  padding: 5px 10px;
  font-size: 0.78rem;
  font-weight: 700;
  margin-bottom: 10px;
}

.profile-2fa-status.is-active {
  border-color: rgba(85, 209, 153, 0.45);
  background: rgba(85, 209, 153, 0.14);
  color: #bff5dd;
}

.profile-2fa-note {
  font-size: 0.85rem;
  color: #c5d7e7;
  margin-top: 8px;
}

.profile-flash {
  border-radius: 12px;
  padding: 10px 12px;
  margin-bottom: 12px;
  border: 1px solid;
  font-size: 0.88rem;
}

.profile-flash.ok {
  color: #bff5dd;
  border-color: rgba(85, 209, 153, 0.45);
  background: rgba(85, 209, 153, 0.12);
}

.profile-flash.error {
  color: #ffd0d0;
  border-color: rgba(243, 125, 125, 0.45);
  background: rgba(243, 125, 125, 0.12);
}

.profile-2fa-form {
  margin-top: 12px;
  display: grid;
  gap: 10px;
}

.profile-2fa-form input {
  width: 100%;
  border-radius: 10px;
  border: 1px solid var(--p-line);
  background: rgba(8, 17, 27, 0.92);
  color: var(--p-text);
  padding: 10px 12px;
}

.profile-section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 4px;
}

.profile-section-head .profile-tag {
  margin-bottom: 0;
}

.profile-orders {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.profile-order-card {
  background: linear-gradient(180deg, rgba(13, 25, 39, 0.95), rgba(10, 19, 29, 0.78));
  border: 1px solid var(--p-line);
  border-radius: 20px;
  padding: 22px;
  box-shadow: 0 22px 52px rgba(0, 0, 0, 0.34);
  transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
}

.profile-order-card:hover {
  transform: translateY(-4px);
  border-color: rgba(29, 217, 183, 0.45);
  box-shadow: 0 26px 54px rgba(0, 0, 0, 0.45);
}

.profile-badges {
  display: flex;
  gap: 8px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.profile-badges span {
  background: rgba(0, 210, 184, 0.15);
  color: #6ff7e5;
  border: 1px solid rgba(0, 210, 184, 0.35);
  border-radius: 999px;
  padding: 4px 8px;
  font-size: 0.7rem;
}

.profile-empty {
  text-align: center;
}

.profile-empty p {
  margin-bottom: 14px;
}

@media (max-width: 980px) {
  .profile-fallback .footer-top {
    grid-template-columns: 1fr;
  }

  .profile-fallback .footer-links {
    justify-content: flex-start;
  }

  .profile-fallback .topbar-contact {
    display: none;
  }

  .profile-fallback .topbar-nav {
    gap: 10px 14px;
  }

  .profile-hero,
  .profile-quick ul,
  .profile-split,
  .profile-orders {
    grid-template-columns: 1fr;
  }

  .profile-section-head {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
}
</style>

<div class="profile-page">
  <?php if ($profileNotice !== ''): ?>
    <div class="profile-flash ok"><?= htmlspecialchars($profileNotice, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if ($profileError !== ''): ?>
    <div class="profile-flash error"><?= htmlspecialchars($profileError, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <section class="profile-hero">
    <article class="profile-card">
      <p class="profile-tag">CLIENT DASHBOARD</p>
      <h1>Mon espace client Sazulis</h1>
      <p>Bienvenue <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?>. Suivi commandes, infos compte et actions rapides au meme endroit.</p>
      <div class="profile-actions">
        <a class="profile-btn profile-btn-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop">Continuer mes achats</a>
        <a class="profile-btn profile-btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/contact">Contacter Sazulis</a>
      </div>
    </article>

    <aside class="profile-kpis">
      <div class="profile-kpi">
        <strong><?= $orderCount ?></strong>
        <span>Commandes</span>
      </div>
      <div class="profile-kpi">
        <strong><?= number_format($totalSpent, 2, ',', ' ') ?> EUR</strong>
        <span>Total depense</span>
      </div>
      <div class="profile-kpi">
        <strong><?= htmlspecialchars($latestOrderDate ?? 'Aucune', ENT_QUOTES, 'UTF-8') ?></strong>
        <span>Derniere commande</span>
      </div>
      <div class="profile-kpi">
        <strong><?= htmlspecialchars($memberSince, ENT_QUOTES, 'UTF-8') ?></strong>
        <span>Membre depuis</span>
      </div>
    </aside>
  </section>

  <section class="profile-card profile-quick">
    <h2>Pilotage rapide du compte</h2>
    <ul>
      <li>Type de compte: Client</li>
      <li>Email: <?= htmlspecialchars($customerEmail !== '' ? $customerEmail : 'Non renseigne', ENT_QUOTES, 'UTF-8') ?></li>
      <li>En attente: <?= $pending ?></li>
      <li>En cours: <?= $inProgress ?></li>
      <li>Validees: <?= $done ?></li>
      <li>Annulees: <?= $cancelled ?></li>
    </ul>
  </section>

  <section class="profile-split">
    <article class="profile-card">
      <h3>Identite client</h3>
      <div class="profile-account-lines">
        <p><strong>Nom:</strong> <?= htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($customerEmail !== '' ? $customerEmail : 'Non renseigne', ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>Role:</strong> Client</p>
      </div>
    </article>

    <article class="profile-card">
      <h3>Support prioritaire</h3>
      <p>Besoin d'une modification de commande, d'une facture ou d'un accompagnement sur ton projet ?</p>
      <div class="profile-actions">
        <a class="profile-btn profile-btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/contact">Ouvrir le support</a>
      </div>
    </article>

    <article class="profile-card profile-2fa-card">
      <h3>Securite du compte</h3>
      <p class="profile-2fa-status <?= $twofaEnabled ? 'is-active' : '' ?>">
        <?= $twofaEnabled ? '2FA activee' : '2FA inactive' ?>
      </p>
      <?php if ($twofaEnabled): ?>
        <p>Ton compte est protege par la double authentification. Un code temporaire est demande a chaque connexion.</p>
        <form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/2fa/disable" class="profile-2fa-form">
          <input type="hidden" name="__csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
          <label>Mot de passe actuel (requis pour desactiver)
            <input type="password" name="current_password" required>
          </label>
          <button class="profile-btn profile-btn-ghost" type="submit">Desactiver la 2FA</button>
        </form>
        <p class="profile-2fa-note">Si tu changes de telephone, reconfigure la 2FA pour conserver l'acces.</p>
      <?php else: ?>
        <p>Tu peux activer la double authentification maintenant pour renforcer la protection de ton compte.</p>
        <div class="profile-actions">
          <a class="profile-btn profile-btn-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/2fa/setup?origin=profile">Activer la 2FA</a>
        </div>
        <p class="profile-2fa-note">Recommande: active la 2FA pour eviter les connexions non autorisees.</p>
      <?php endif; ?>
    </article>
  </section>

  <section class="profile-card profile-documents">
    <div class="profile-section-head">
      <h2>Documents client</h2>
      <span class="profile-tag">DOWNLOADS</span>
    </div>
    <?php if ($latestOrderId !== ''): ?>
      <p class="profile-document-note">Télécharge ou visualise directement le contrat et la facture de ta dernière commande.</p>
      <div class="profile-document-row">
        <a class="profile-btn profile-btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile/documents/<?= (int) $latestOrderId ?>/contract/preview">Aperçu contrat</a>
        <a class="profile-btn profile-btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile/documents/<?= (int) $latestOrderId ?>/contract">Télécharger le contrat</a>
        <a class="profile-btn profile-btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile/documents/<?= (int) $latestOrderId ?>/invoice/preview">Aperçu facture</a>
        <a class="profile-btn profile-btn-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile/documents/<?= (int) $latestOrderId ?>/invoice">Télécharger la facture</a>
      </div>
    <?php else: ?>
      <p class="profile-document-note">Aucun document disponible tant qu’aucune commande n’a été passée.</p>
    <?php endif; ?>
  </section>

  <div class="profile-section-head">
    <h2>Commandes recentes</h2>
    <span class="profile-tag">ORDER TRACKING</span>
  </div>

  <?php if (empty($orders)): ?>
    <section class="profile-card profile-empty">
      <h2>Aucune commande pour le moment</h2>
      <p>Ajoute des produits au panier puis valide ta premiere commande.</p>
      <a class="profile-btn profile-btn-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop">Aller au catalogue</a>
    </section>
  <?php else: ?>
    <section class="profile-orders">
      <?php foreach ($orders as $order): ?>
        <?php
        $items = isset($order['items']) && is_array($order['items']) ? $order['items'] : [];
        $itemsCount = 0;
        $orderId = (int) ($order['id'] ?? 0);
        $acompte = (int) ($order['acompte_paye'] ?? 0) === 1;
        $solde = (int) ($order['solde_regle'] ?? 0) === 1;
        $signaturePath = (string) ($order['client_signature_path'] ?? '');
        $hasSignature = $signaturePath !== '';
        foreach ($items as $item) {
            $itemsCount += (int) ($item['quantity'] ?? 0);
        }
        ?>
        <article class="profile-order-card">
          <div class="profile-badges">
            <span><?= htmlspecialchars($statusLabel((string) ($order['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8') ?></span>
            <span><?= (int) $itemsCount ?> article(s)</span>
            <span><?= $acompte ? 'Acompte valide' : 'Acompte en attente' ?></span>
            <span><?= $solde ? 'Solde regle' : 'Solde en attente' ?></span>
            <span><?= $hasSignature ? 'Document signe' : 'Signature requise' ?></span>
          </div>
          <h3>Commande <?= htmlspecialchars((string) ($order['order_ref'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
          <p>Date: <?= htmlspecialchars((string) ($order['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
          <p>Total: <strong><?= number_format((float) ($order['total'] ?? 0), 2, ',', ' ') ?> EUR</strong></p>
          <div class="profile-actions profile-order-actions">
            <a class="profile-btn profile-btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile/documents/<?= $orderId ?>/contract/preview">Aperçu contrat</a>
            <a class="profile-btn profile-btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile/documents/<?= $orderId ?>/contract">Télécharger le contrat</a>
            <a class="profile-btn profile-btn-ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile/documents/<?= $orderId ?>/invoice/preview">Aperçu facture</a>
            <a class="profile-btn profile-btn-primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile/documents/<?= $orderId ?>/invoice">Télécharger la facture</a>
          </div>

          <div class="profile-signature-box">
            <?php if ($hasSignature): ?>
              <p>Signature client enregistree.</p>
              <img class="profile-signature-preview" src="<?= htmlspecialchars($basePath . $signaturePath, ENT_QUOTES, 'UTF-8') ?>" alt="Signature client">
            <?php else: ?>
              <p>Signe en ligne ce document pour finaliser le contrat.</p>
              <form method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/profile/signature/<?= $orderId ?>" class="js-signature-form" data-order-id="<?= $orderId ?>">
                <input type="hidden" name="__csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <canvas class="profile-signature-canvas js-signature-canvas" width="540" height="140"></canvas>
                <input type="hidden" name="signature_data" class="js-signature-input" value="">
                <div class="profile-signature-actions">
                  <button type="button" class="profile-btn profile-btn-ghost js-signature-clear">Effacer</button>
                  <button type="submit" class="profile-btn profile-btn-primary">Enregistrer la signature</button>
                </div>
              </form>
            <?php endif; ?>
          </div>

          <?php if (!empty($items)): ?>
            <p>
              <?php
              $parts = [];
              foreach ($items as $item) {
                  $parts[] = (string) ($item['product_name'] ?? '') . ' x' . (int) ($item['quantity'] ?? 0);
              }
              echo htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8');
              ?>
            </p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>
</div>

<script>
document.querySelectorAll('.js-signature-form').forEach((form) => {
  const canvas = form.querySelector('.js-signature-canvas');
  const input = form.querySelector('.js-signature-input');
  const clearBtn = form.querySelector('.js-signature-clear');
  if (!canvas || !input || !clearBtn) {
    return;
  }

  const context = canvas.getContext('2d');
  let drawing = false;

  const resetCanvas = () => {
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.strokeStyle = '#111111';
    context.lineWidth = 2;
    context.lineCap = 'round';
  };

  const pointerPos = (event) => {
    const rect = canvas.getBoundingClientRect();
    return {
      x: (event.clientX - rect.left) * (canvas.width / rect.width),
      y: (event.clientY - rect.top) * (canvas.height / rect.height),
    };
  };

  resetCanvas();

  canvas.addEventListener('pointerdown', (event) => {
    drawing = true;
    const p = pointerPos(event);
    context.beginPath();
    context.moveTo(p.x, p.y);
  });

  canvas.addEventListener('pointermove', (event) => {
    if (!drawing) {
      return;
    }
    const p = pointerPos(event);
    context.lineTo(p.x, p.y);
    context.stroke();
  });

  const stopDrawing = () => {
    drawing = false;
  };

  canvas.addEventListener('pointerup', stopDrawing);
  canvas.addEventListener('pointerleave', stopDrawing);

  clearBtn.addEventListener('click', () => {
    resetCanvas();
    input.value = '';
  });

  form.addEventListener('submit', (event) => {
    input.value = canvas.toDataURL('image/png');
    if (!input.value || input.value.length < 400) {
      event.preventDefault();
      alert('Merci de signer avant de valider.');
    }
  });
});
</script>

  <?php if (!$isInLayout): ?>
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
  <script>window.SAZULIS_USER = <?= $layoutUser ? json_encode($layoutUser, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : 'null' ?>;</script>
  <script>window.SAZULIS_CSRF = '<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>';</script>
  <script src="<?= htmlspecialchars($assetsBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/js/app.js" defer></script>
  </body>
  </html>
  <?php endif; ?>
