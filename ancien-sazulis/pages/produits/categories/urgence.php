
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Ajout gestion 5 langues
$lang = 'fr';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr','en','es','de','it'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['fr','en','es','de','it'])) {
    $lang = $_SESSION['lang'];
}
$langs = include '../../../lang/' . $lang . '.php';
include '../../../navbar.php';
?><!DOCTYPE html>
<html lang="<?=$lang?>">
<head>
    <meta charset="UTF-8">
    <title><?php
        if ($lang=="fr") echo "Urgence";
        elseif ($lang=="en") echo "Emergency";
        elseif ($lang=="es") echo "Urgencia";
        elseif ($lang=="de") echo "Notfall";
        elseif ($lang=="it") echo "Emergenza";
    ?></title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
<?php require_once __DIR__ . '/../../../protect.php'; ?>
<body>
<main>
    <section class="category-hero" style="background: linear-gradient(90deg, #ffe9c6 60%, #ffd700 100%); padding: 2.5em 0 1.5em 0; border-radius: 0 0 32px 32px; box-shadow: 0 4px 24px #ffd70033; margin-bottom:2em;">
        <div style="text-align:center;">
                        <img src="../../../assets/img/urgent.png" alt="Maintenance Urgente" style="width:90px;height:90px;border-radius:50%;box-shadow:0 2px 12px #ffd70055;margin-bottom:1em;border:4px solid #fff;">
            <h1 style="font-size:2.2em;font-weight:800;color:#b85c00;margin-bottom:0.2em;letter-spacing:0.02em;">
                <?php
                if ($lang=="fr") echo "Intervention Urgente";
                elseif ($lang=="en") echo "Emergency Intervention";
                elseif ($lang=="es") echo "Intervención Urgente";
                elseif ($lang=="de") echo "Notfalleinsatz";
                elseif ($lang=="it") echo "Intervento d'Emergenza";
                ?>
            </h1>
            <div style="display:inline-block;background:#ff3c00;color:#fff;font-weight:700;padding:0.3em 1.2em;border-radius:18px;font-size:1.1em;box-shadow:0 1px 6px #ff3c0033;margin-bottom:0.7em;">
                <?php
                if ($lang=="fr") echo "Priorité immédiate";
                elseif ($lang=="en") echo "Immediate Priority";
                elseif ($lang=="es") echo "Prioridad inmediata";
                elseif ($lang=="de") echo "Sofortige Priorität";
                elseif ($lang=="it") echo "Priorità immediata";
                ?>
            </div>
            <p style="max-width:500px;margin:1em auto 0 auto;font-size:1.15em;color:#333;">
                <?php
                if ($lang=="fr") echo "Besoin d'une intervention rapide sur votre site&nbsp;? Notre équipe intervient en urgence 7j/7 pour résoudre vos problèmes critiques et garantir la continuité de votre activité.";
                elseif ($lang=="en") echo "Need a quick intervention on your website? Our team responds urgently 7 days a week to solve your critical issues and ensure your business continuity.";
                else echo "¿Necesita una intervención rápida en su sitio? Nuestro equipo interviene de urgencia 7d/7 para resolver sus problemas críticos y garantizar la continuidad de su actividad.";
                ?>
            </p>
        </div>
    </section>
    <div class="product-grid" style="margin-top:0;">
        <div class="product-card fiche-produit-ultra" style="border:2px solid #ff3c00;box-shadow:0 2px 16px #ff3c0033;position:relative;">
            <span style="position:absolute;top:12px;right:12px;background:#ff3c00;color:#fff;font-size:0.95em;padding:0.2em 0.8em;border-radius:16px;font-weight:700;box-shadow:0 1px 4px #ff3c0033;">
                <?php
                if ($lang=="fr") echo "Ultra-Rapide";
                elseif ($lang=="en") echo "Ultra-Fast";
                else echo "Ultra-Rápido";
                ?>
            </span>
            <img src="../../../assets/img/urgent.png" alt="Maintenance Urgente" style="margin-top:1em;">
            <div class="product-title" style="font-size:1.25em;font-weight:700;margin:0.7em 0 0.3em 0;">
                <?php
                if ($lang=="fr") echo "Maintenance Urgente";
                elseif ($lang=="en") echo "Emergency Maintenance";
                else echo "Mantenimiento Urgente";
                ?>
            </div>
            <div style="font-size:1.15em;color:#d2691e;font-weight:700;margin-bottom:0.5em;">200,00&nbsp;€ TTC</div>
            <ul style="list-style:none;padding:0;margin:0 0 1em 0;font-size:1em;color:#444;">
                <li style="margin-bottom:0.3em;"><span style="color:#ff3c00;font-weight:700;">&#9888;</span> <?php
                    if ($lang=="fr") echo "Prise en charge sous 2h";
                    elseif ($lang=="en") echo "Handled within 2h";
                    else echo "Atención en menos de 2h";
                ?></li>
                <li style="margin-bottom:0.3em;"><span style="color:#ffd700;font-weight:700;">&#128295;</span> <?php
                    if ($lang=="fr") echo "Diagnostic & réparation immédiate";
                    elseif ($lang=="en") echo "Immediate diagnosis & repair";
                    else echo "Diagnóstico y reparación inmediata";
                ?></li>
                <li><span style="color:#007bff;font-weight:700;">&#128222;</span> <?php
                    if ($lang=="fr") echo "Support 7j/7";
                    elseif ($lang=="en") echo "Support 7d/7";
                    else echo "Soporte 7d/7";
                ?></li>
            </ul>
            <a href="../../../pages/produits/maintenance-urgente.php"><button class="product-btn" style="background:linear-gradient(90deg,#ff3c00,#ffd700 90%);color:#fff;font-weight:700;">
                <?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?>
            </button></a>
        </div>
    </div>
</main>
<?php include '../../../footer.php'; ?>
</body>
</html>
