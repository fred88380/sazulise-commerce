
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
    <title><?=isset($langs['products_maintenance']) ? $langs['products_maintenance'] : 'Maintenance'?></title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
<?php require_once __DIR__ . '/../../../protect.php'; ?>
<body>
<main>
    <h1 style="text-align:center;margin:1em 0;">
        <?php
        if ($lang=="fr") echo "Maintenance";
        elseif ($lang=="en") echo "Maintenance";
        elseif ($lang=="es") echo "Mantenimiento";
        elseif ($lang=="de") echo "Wartung";
        elseif ($lang=="it") echo "Manutenzione";
        ?>
    </h1>
    <div class="product-grid">
        <div class="product-card">
            <img src="../../../assets/img/maintenance.jpg" alt="Maintenance Basic">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Maintenance Basic";
                elseif ($lang=="en") echo "Basic Maintenance";
                elseif ($lang=="es") echo "Mantenimiento Básico";
                elseif ($lang=="de") echo "Basiswartung";
                elseif ($lang=="it") echo "Manutenzione Base";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">350,00&nbsp;€ TTC</div>
            <a href="../../../pages/produits/maintenance-basic.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/maintenance.jpg" alt="Maintenance Business">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Maintenance Business";
                elseif ($lang=="en") echo "Business Maintenance";
                else echo "Mantenimiento Business";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">650,00&nbsp;€ TTC</div>
            <a href="../../../pages/produits/maintenance-business.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/maintenance.jpg" alt="Maintenance Premium">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Maintenance Premium";
                elseif ($lang=="en") echo "Premium Maintenance";
                else echo "Mantenimiento Premium";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">950,00&nbsp;€ TTC</div>
            <a href="../../../pages/produits/maintenance-premium.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/urgent.png" alt="Maintenance Urgente">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Maintenance Urgente";
                elseif ($lang=="en") echo "Urgent Maintenance";
                else echo "Mantenimiento Urgente";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">200,00&nbsp;€ TTC</div>
            <a href="../../../pages/produits/maintenance-urgente.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
    </div>
</main>
<?php include '../../../footer.php'; ?>
</body>
</html>
