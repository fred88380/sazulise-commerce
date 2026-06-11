<?php
// Détection du contexte pour les chemins relatifs (produits, pages ou racine)
if (strpos($_SERVER['REQUEST_URI'], '/pages/produits/') !== false) {
    $base = '../../';
} elseif (strpos($_SERVER['REQUEST_URI'], '/pages/') !== false) {
    $base = '../';
} else {
    $base = '';
}
?>
<footer class="footer-modern" style="background:#fff;border-top:1px solid #eee;padding:2.2em 0 1.2em 0;text-align:center;margin-top:3em;width:100vw;max-width:100vw;overflow-x:hidden;">
    <div style="margin-bottom:1.1em;display:flex;align-items:center;justify-content:center;gap:1.2em;">
        <img src="<?= $base ?>assets/img/sazulis-logo1.png" alt="Logo Sazulis" style="height: 115px;width:auto;vertical-align:middle;opacity:0.95;">
        <nav class="footer-links" style="display:flex;flex-wrap:wrap;align-items:center;gap:1.3em;font-size:1.09em;margin-bottom:0;">
            <a href="<?= $base ?>pages/mentions-legales.php" style="color:#1a2347;text-decoration:none;">Mentions légales</a>
            <a href="<?= $base ?>pages/apropos.php" style="color:#1a2347;text-decoration:none;">À propos</a>
            <a href="<?= $base ?>pages/cgu.php" style="color:#1a2347;text-decoration:none;">CGU</a>
            <a href="<?= $base ?>pages/cgv.php" style="color:#1a2347;text-decoration:none;">CGV</a>
            <a href="<?= $base ?>pages/paiement-securise.php" style="color:#1a2347;text-decoration:none;">Paiement sécurisé</a>
            <a href="<?= $base ?>pages/conditions-livraison.php" style="color:#1a2347;text-decoration:none;">Conditions de livraison</a>
            <a href="<?= $base ?>pages/partenaire.php" style="color:#1a2347;text-decoration:none;">Partenaires</a>
            <a href="<?= $base ?>pages/creations.php" style="color:#1a2347;text-decoration:none;">Créations</a>
        </nav>
    </div>
    <p style="color:#888;font-size:1em;margin-bottom:0;">Conçu par Sazulis &copy; 2026</p>
</footer>
