
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
        if ($lang=="fr") echo "WordPress";
        elseif ($lang=="en") echo "WordPress";
        elseif ($lang=="es") echo "WordPress";
        elseif ($lang=="de") echo "WordPress";
        elseif ($lang=="it") echo "WordPress";
    ?></title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
<?php require_once __DIR__ . '/../../../protect.php'; ?>
<body>
<main>
    <h1 style="text-align:center;margin:1em 0;">
        <?php
        if ($lang=="fr") echo "WordPress";
        elseif ($lang=="en") echo "WordPress";
        elseif ($lang=="es") echo "WordPress";
        elseif ($lang=="de") echo "WordPress";
        elseif ($lang=="it") echo "WordPress";
        ?>
    </h1>
    <div class="product-grid">
        <div class="product-card">
            <img src="../../../assets/img/wordpress.jpg" alt="WordPress CMS HTML">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "WordPress CMS HTML";
                elseif ($lang=="en") echo "WordPress CMS HTML";
                elseif ($lang=="es") echo "WordPress CMS HTML";
                elseif ($lang=="de") echo "WordPress CMS HTML";
                elseif ($lang=="it") echo "WordPress CMS HTML";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">3 603,64&nbsp;€ TTC</div>
            <a href="../../../pages/produits/wordpress-cms-html.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/wordpress.jpg" alt="WordPress CMS Next.js">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "WordPress CMS Next.js";
                elseif ($lang=="en") echo "WordPress CMS Next.js";
                else echo "WordPress CMS Next.js";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">4 675,87&nbsp;€ TTC</div>
            <a href="../../../pages/produits/wordpress-cms-nextjs.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
    </div>
</main>
<?php include '../../../footer.php'; ?>
</body>
</html>
