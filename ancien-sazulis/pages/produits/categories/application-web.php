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
    <title><?=isset($langs['products_application_web']) ? $langs['products_application_web'] : 'Application Web'?></title>
    <link rel="stylesheet" href="../../../assets/css/style.css">
</head>
<body>
<main>
    <h1 style="text-align:center;margin:1em 0;">
        <?php
        if ($lang=="fr") echo "Applications Web";
        elseif ($lang=="en") echo "Web Applications";
        elseif ($lang=="es") echo "Aplicaciones Web";
        elseif ($lang=="de") echo "Webanwendungen";
        elseif ($lang=="it") echo "Applicazioni Web";
        ?>
    </h1>
    <div class="product-grid">
        <div class="product-card">
            <img src="../../../assets/img/application-web.png" alt="Application Web HTML">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Application Web HTML";
                elseif ($lang=="en") echo "Web Application HTML";
                elseif ($lang=="es") echo "Aplicación Web HTML";
                elseif ($lang=="de") echo "Webanwendung HTML";
                elseif ($lang=="it") echo "Applicazione Web HTML";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">5 015,13&nbsp;€ TTC</div>
            <a href="../application-web-html.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
        <div class="product-card">
            <img src="../../../assets/img/application-web.png" alt="Application Web Next.js">
            <div class="product-title">
                <?php
                if ($lang=="fr") echo "Application Web Next.js";
                elseif ($lang=="en") echo "Web Application Next.js";
                else echo "Aplicación Web Next.js";
                ?>
            </div>
            <div style="font-size:1.05em;color:#d2691e;font-weight:700;margin-bottom:0.3em;">6 087,36&nbsp;€ TTC</div>
            <a href="../application-web-nextjs.php"><button class="product-btn"><?php echo isset($langs['products_see_sheet']) ? $langs['products_see_sheet'] : 'Voir la fiche'; ?></button></a>
        </div>
    </div>
</main>
<?php include '../../../footer.php'; ?>
</body>
</html>
