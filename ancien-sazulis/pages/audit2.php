<?php
include_once __DIR__ . '/../bootstrap.php'; 
require_once __DIR__ . '/../protect.php'; // Chemin correct car on est déjà dans un sous-dossier
// Détection de la langue (URL > cookie > défaut)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$lang = 'fr';
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    setcookie('lang', $lang, time() + (3600 * 24 * 30), "/");
} elseif (isset($_COOKIE['lang'])) {
    $lang = $_COOKIE['lang'];
}
$langs = include __DIR__ . '/../lang/' . $lang . '.php';
include '../navbar.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<?php include __DIR__ . '/../head.php'; ?>
    <style>
        main.audit2-main {
            min-height: 100%;
            position: relative;
            overflow: hidden;
            margin-top: -50px;
            margin-bottom: -100px;
        }
        main.audit2-main::before {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('../assets/img/audit2.png') center/cover no-repeat scroll;
            width: 100%;
            height: 100%;
            z-index: -1;
            
        }
    </style>
<body>
    <main class="audit2-main"></main>
    <?php include '../footer.php'; ?>
</body>
</html>
