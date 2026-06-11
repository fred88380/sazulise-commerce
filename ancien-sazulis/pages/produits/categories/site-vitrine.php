
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
        if ($lang=="fr") echo "Site Vitrine";
        elseif ($lang=="en") echo "Showcase Website";
        else echo "Sitio Vitrina";
    ?></title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
<?php require_once __DIR__ . '/../../../protect.php'; ?>
<body>
<main>
    <h1 style="text-align:center;margin:1em 0;">
        <?php
        if ($lang=="fr") echo "Sites Vitrines";
        elseif ($lang=="en") echo "Showcase Websites";
        elseif ($lang=="es") echo "Sitios Vitrina";
        elseif ($lang=="de") echo "Schaufenster-Websites";
        elseif ($lang=="it") echo "Siti Vetrina";
        ?>
    </h1>
    <div class="product-grid">
        <div class="product-card">
            <img src="../../../assets/img/site-vitrine.png" alt="Site vitrine HTML">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Site vitrine HTML";
                elseif ($lang=="en") echo "Showcase Website HTML";
                elseif ($lang=="es") echo "Sitio vitrina HTML";
                elseif ($lang=="de") echo "Schaufenster-Website HTML";
                elseif ($lang=="it") echo "Sito vetrina HTML";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">4 326,87&nbsp;€ TTC</div>
            <a href="../site-vitrine-html.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/site-vitrine.png" alt="Site vitrine Next.js">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Site vitrine Next.js";
                elseif ($lang=="en") echo "Showcase Website Next.js";
                elseif ($lang=="es") echo "Sitio vitrina Next.js";
                elseif ($lang=="de") echo "Schaufenster-Website Next.js";
                elseif ($lang=="it") echo "Sito vetrina Next.js";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">5 399,10&nbsp;€ TTC</div>
            <a href="../site-vitrine-nextjs.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
    </div>
</main>
<?php include '../../../footer.php'; ?>
</body>
</html>
