
<?php
require_once __DIR__ . '/security_headers.php';
// navbar.php
// IMPORTANT : pas de session_start(), pas de setcookie(), pas de include de langues ici.
// On attend $lang et $langs (chargés via /bootstrap.php).

// Détection du contexte pour les chemins relatifs (produits, pages ou racine)
if (strpos($_SERVER['REQUEST_URI'], '/pages/produits/') !== false) {
    $base = '../../';
} elseif (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false) {
    $base = '../';
} else {
    $base = '';
}
?>

<div class="topbar-contact" style="background:#1a2347;color:#fff;display:flex;align-items:center;justify-content:center;gap:2.2em;padding:0.55em 0 0.55em 0;font-size:1.08em;font-family:'Segoe UI',Arial,sans-serif;box-shadow:0 2px 12px #0001;width:100%;max-width:100vw;margin:0;z-index:100;text-align:center;letter-spacing:0.5px;overflow-x:hidden;">
    <span class="topbar-item" style="margin:0 0.5em;font-weight:600;white-space:nowrap;">SAZULIS Développeur web</span>
    <span class="topbar-item" style="margin:0 0.5em;white-space:nowrap;"><a href="tel:0698766780" style="color:#fff;text-decoration:underline;font-weight:500;">06 98 76 67 80</a></span>
    <span class="topbar-item" style="margin:0 0.5em;white-space:nowrap;">SIRET : 752 628 040 00020</span>
    <span class="topbar-item" style="margin:0 0.5em;white-space:nowrap;"><a href="mailto:contact@sazulis.fr" style="color:#fff;text-decoration:underline;font-weight:500;">contact@sazulis.fr</a></span>
    <span class="topbar-item" style="margin:0 0.5em;white-space:nowrap;">Vosges & France entière</span>
</div>

<nav class="navbar-modern" style="background:#fff;box-shadow:0 2px 12px #0001;padding:0.5em 0 0.5em 0;display:flex;align-items:center;justify-content:center;width:100%;max-width:100vw;margin:0;overflow-x:hidden;">
    <div class="navbar-logo" style="margin-right:2em;">
        <a href="<?= $base ?>index.php"><img src="<?= $base ?>assets/img/sazulis-logo1.png" alt="Logo Sazulis" style="height:48px;width:auto;vertical-align:middle;"></a>
    </div>
    <ul style="display:flex;gap:2em;align-items:center;list-style:none;margin:0;padding:0;font-size:1.13em;font-weight:600;flex-wrap:wrap;">
        <li><a href="<?= $base ?>index.php" style="color:#1a2347;text-decoration:none;">Accueil</a></li>
        <li><a href="<?= $base ?>pages/products.php" style="color:#1a2347;text-decoration:none;">Boutique</a></li>
        <li><a href="<?= $base ?>pages/contact.php" style="color:#1a2347;text-decoration:none;">Contact</a></li>
        <li><a href="<?= $base ?>pages/audit.php" style="color:#1a2347;text-decoration:none;">Audit</a></li>
        <li>
            <a href="<?= $base ?>pages/panier.php" style="color:#1a2347;text-decoration:none;">
                Panier<?php if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && count($_SESSION['cart']) > 0) { echo ' (' . count($_SESSION['cart']) . ')'; } ?>
            </a>
        </li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="<?= $base ?>pages/profil.php" style="color:#1a2347;text-decoration:none;">👤 Profil</a></li>
            <li><a href="<?= $base ?>pages/logout.php" style="color:#d9534f;text-decoration:none;">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="<?= $base ?>pages/connexion.php" style="color:#1a2347;text-decoration:none;">Connexion</a></li>
        <?php endif; ?>
    </ul>
</nav>

<script>
    function setDaltonienMode(active) {
        if(active) {
            document.body.classList.add('daltonien-mode');
            localStorage.setItem('daltonienMode', '1');
        } else {
            document.body.classList.remove('daltonien-mode');
            localStorage.setItem('daltonienMode', '0');
        }
        updateDaltonienBtn();
    }
    function updateDaltonienBtn() {
        const btn = document.getElementById('daltonien-btn');
        if (!btn) return;
        const modeOn = btn.getAttribute('data-mode-on') || 'Mode daltonien';
        const modeOff = btn.getAttribute('data-mode-off') || 'Désactiver le mode daltonien';
        if(document.body.classList.contains('daltonien-mode')) {
            btn.textContent = modeOff;
            btn.style.background = 'linear-gradient(90deg,#b8860b,#ffd700)';
            btn.style.color = '#fff';
        } else {
            btn.textContent = modeOn;
            btn.style.background = 'linear-gradient(90deg,#ffe9c6,#ffd700)';
            btn.style.color = '#333';
        }
    }


    document.addEventListener('DOMContentLoaded', function() {
        // Daltonien
        const btn = document.getElementById('daltonien-btn');
        if(localStorage.getItem('daltonienMode') === '1') {
            document.body.classList.add('daltonien-mode');
        }
        updateDaltonienBtn();
        if (btn) {
            btn.addEventListener('click', function() {
                setDaltonienMode(!document.body.classList.contains('daltonien-mode'));
            });
        }
    });
</script>

<style>
.daltonien-mode {
    filter: contrast(1.2) saturate(0.7) hue-rotate(30deg);
}
#lang-switcher .lang-btn:focus {
    outline: 2px solid #ffd700;
}
</style>
