<?php
require_once __DIR__ . '/../../../protect.php';
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
    <title><?=isset($langs['products_hosting']) ? $langs['products_hosting'] : 'Hébergement'?></title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
</head>
<body>
<main>
    <h1 style="text-align:center;margin:1em 0;">
        <?php
        if ($lang=="fr") echo "Hébergement";
        elseif ($lang=="en") echo "Hosting";
        elseif ($lang=="es") echo "Alojamiento";
        elseif ($lang=="de") echo "Hosting";
        elseif ($lang=="it") echo "Hosting";
        ?>
    </h1>
    <div class="product-grid">
        <div class="product-card">
            <img src="../../../assets/img/hebergement.jpg" alt="Hébergement Basic HTML">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Hébergement Basic HTML";
                elseif ($lang=="en") echo "Basic HTML Hosting";
                elseif ($lang=="es") echo "Alojamiento Básico HTML";
                elseif ($lang=="de") echo "Basic HTML Hosting";
                elseif ($lang=="it") echo "Basic HTML Hosting";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">149,99&nbsp;€ TTC</div>
            <a href="../../../pages/produits/hebergement-basic-html.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/hebergement.jpg" alt="Hébergement Basic Next.js">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Hébergement Basic Next.js";
                elseif ($lang=="en") echo "Basic Next.js Hosting";
                else echo "Alojamiento Básico Next.js";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">299,99&nbsp;€ TTC</div>
            <a href="../../../pages/produits/hebergement-basic-nextjs.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/hebergement.jpg" alt="Hébergement Business HTML">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Hébergement Business HTML";
                elseif ($lang=="en") echo "Business HTML Hosting";
                else echo "Alojamiento Business HTML";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">289,99&nbsp;€ TTC</div>
            <a href="../../../pages/produits/hebergement-business-html.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/hebergement.jpg" alt="Hébergement Business Next.js">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Hébergement Business Next.js";
                elseif ($lang=="en") echo "Business Next.js Hosting";
                else echo "Alojamiento Business Next.js";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">579,98&nbsp;€ TTC</div>
            <a href="../../../pages/produits/hebergement-business-nextjs.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/hebergement.jpg" alt="Hébergement Premium HTML">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Hébergement Premium HTML";
                elseif ($lang=="en") echo "Premium HTML Hosting";
                else echo "Alojamiento Premium HTML";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">579,99&nbsp;€ TTC</div>
            <a href="../../../pages/produits/hebergement-premium-html.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/hebergement.jpg" alt="Hébergement Premium Next.js">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Hébergement Premium Next.js";
                elseif ($lang=="en") echo "Premium Next.js Hosting";
                else echo "Alojamiento Premium Next.js";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">1 159,96&nbsp;€ TTC</div>
            <a href="../../../pages/produits/hebergement-premium-nextjs.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
    </div>
</main>
<?php include '../../../footer.php'; ?>
</body>
</html>
