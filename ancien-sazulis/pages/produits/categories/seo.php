
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
    <title><?=isset($langs['products_seo']) ? $langs['products_seo'] : 'SEO'?></title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
<?php require_once __DIR__ . '/../../../protect.php'; ?>
<body>
<main>
    <h1 style="text-align:center;margin:1em 0;">
        <?php
        if ($lang=="fr") echo "SEO";
        elseif ($lang=="en") echo "SEO";
        elseif ($lang=="es") echo "SEO";
        elseif ($lang=="de") echo "SEO";
        elseif ($lang=="it") echo "SEO";
        ?>
    </h1>
    <div class="product-grid">
        <div class="product-card">
            <img src="../../../assets/img/seo.png" alt="SEO Basic">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "SEO Basic";
                elseif ($lang=="en") echo "Basic SEO";
                elseif ($lang=="es") echo "SEO Básico";
                elseif ($lang=="de") echo "SEO Basic";
                elseif ($lang=="it") echo "SEO Base";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">299,99&nbsp;€ TTC</div>
            <a href="../../../pages/produits/seo-basic.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/seo.png" alt="SEO Business">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "SEO Business";
                elseif ($lang=="en") echo "Business SEO";
                else echo "SEO Business";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">459,98&nbsp;€ TTC</div>
            <a href="../../../pages/produits/seo-business.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/seo.png" alt="SEO Premium">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "SEO Premium";
                elseif ($lang=="en") echo "Premium SEO";
                else echo "SEO Premium";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">599,99&nbsp;€ TTC</div>
            <a href="../../../pages/produits/seo-premium.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
    </div>
</main>
<?php include '../../../footer.php'; ?>
</body>
</html>
