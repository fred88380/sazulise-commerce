<?php

declare(strict_types=1);

/** @var int $statusCode */
/** @var string $title */
/** @var string $message */
/** @var string $hint */

http_response_code($statusCode);

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = preg_replace('#/public/errors/[^/]+\.php$#', '', $scriptName);
$basePath = ($basePath === null || $basePath === '/' || $basePath === '.') ? '' : rtrim($basePath, '/');
$assetsBasePath = $basePath . '/public';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($statusCode . ' - ' . $title . ' | Sazulis', ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= htmlspecialchars($assetsBasePath, ENT_QUOTES, 'UTF-8') ?>/assets/css/style.css">
  <style>
    .error-wrap {
      width: min(980px, 92vw);
      margin: 36px auto 46px;
      display: grid;
      gap: 18px;
    }

    .error-card {
      background: linear-gradient(180deg, rgba(13, 25, 39, 0.95), rgba(10, 19, 29, 0.78));
      border: 1px solid rgba(255, 255, 255, 0.14);
      border-radius: 20px;
      padding: 28px;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.28);
    }

    .error-code {
      margin: 0;
      font-family: 'Bebas Neue', sans-serif;
      letter-spacing: 0.06em;
      font-size: clamp(2.6rem, 7.5vw, 6.2rem);
      color: #ff6f3f;
      line-height: 0.9;
    }

    .error-title {
      margin: 12px 0 10px;
      font-size: clamp(1.4rem, 3.6vw, 2.2rem);
      color: #eff8ff;
    }

    .error-text {
      margin: 0;
      color: #bfd3e6;
      line-height: 1.6;
      font-size: 1.05rem;
      max-width: 76ch;
    }

    .error-hint {
      margin-top: 14px;
      color: #9cd8c5;
      font-weight: 600;
    }

    .error-actions {
      margin-top: 22px;
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
    }

    .error-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      border: 1px solid transparent;
      text-decoration: none;
      font-weight: 700;
      padding: 10px 16px;
      transition: transform 0.16s ease, box-shadow 0.16s ease;
    }

    .error-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.2);
    }

    .error-btn.primary {
      background: linear-gradient(120deg, #ff6f3f, #ff8f55);
      color: #120704;
    }

    .error-btn.ghost {
      border-color: rgba(255, 255, 255, 0.3);
      color: #eff8ff;
      background: rgba(255, 255, 255, 0.04);
    }
  </style>
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
  </nav>
</header>

<main>
  <section class="error-wrap">
    <article class="error-card">
      <p class="error-code"><?= (int) $statusCode ?></p>
      <h1 class="error-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
      <p class="error-text"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
      <p class="error-hint"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></p>
      <div class="error-actions">
        <a class="error-btn primary" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/">Retour a l'accueil</a>
        <a class="error-btn ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/shop">Aller a la boutique</a>
        <a class="error-btn ghost" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/contact">Contacter le support</a>
      </div>
    </article>
  </section>
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
  </div>
  <p class="footer-bottom">Concu par Sazulis © 2026</p>
</footer>
</body>
</html>
