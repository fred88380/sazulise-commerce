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
    <title><?=isset($langs['products_e_commerce']) ? $langs['products_e_commerce'] : 'E-commerce'?></title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
</head>
<body>
<main>
    <h1 style="text-align:center;margin:1em 0;">
        <?php
        if ($lang=="fr") echo "E-commerce";
        elseif ($lang=="en") echo "E-commerce";
        elseif ($lang=="es") echo "Comercio electrónico";
        elseif ($lang=="de") echo "E-Commerce";
        elseif ($lang=="it") echo "E-commerce";
        ?>
    </h1>
    <div class="product-grid">
        <div class="product-card">
            <img src="../../../assets/img/e-commerce2.png" alt="E-commerce HTML">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "E-commerce HTML";
                elseif ($lang=="en") echo "E-commerce HTML";
                elseif ($lang=="es") echo "Comercio electrónico HTML";
                elseif ($lang=="de") echo "E-Commerce HTML";
                elseif ($lang=="it") echo "E-commerce HTML";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">6 373,63&nbsp;€ TTC</div>
            <a href="../e-commerce-html.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/e-commerce2.png" alt="E-commerce Next.js">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "E-commerce Next.js";
                elseif ($lang=="en") echo "E-commerce Next.js";
                else echo "Comercio electrónico Next.js";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">7 445,86&nbsp;€ TTC</div>
            <a href="../e-commerce-nextjs.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
    </div>
</main>
<?php include '../../../footer.php'; ?>
</body>
</html>
