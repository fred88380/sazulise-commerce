<?php
// telecharger_livraison.php

require_once __DIR__ . '/backend/db.php';
require_once __DIR__ . '/protect.php';

if (empty($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($projectId <= 0) {
    die('Projet invalide.');
}

$stmt = $pdo->prepare('SELECT * FROM projets WHERE id = ? AND id_utilisateur = ?');
$stmt->execute([$projectId, $userId]);
$projet = $stmt->fetch();

if (!$projet) {
    die('Projet non trouvé ou accès refusé.');
}

$accesLivraison = false;
if (
    ($projet['statut'] === 'transfere') ||
    ((int)$projet['avancement'] === 100 && (int)$projet['solde_regle'] === 1)
) {
    $accesLivraison = true;
}

$projetDir = __DIR__ . '/projets_clients/' . $projectId;

if (!is_dir($projetDir)) {
    mkdir($projetDir, 0775, true);
}

if (!$accesLivraison) {
    echo "<h2>Livraison non disponible</h2><p>Votre projet n'est pas encore terminé.</p>";
    exit;
}

/* -------- FONCTIONS -------- */

function listProjectFiles(string $baseDir): array
{
    $result = [];

    if (!is_dir($baseDir)) return $result;

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($it as $fileInfo) {
        if (!$fileInfo->isFile()) continue;

        $fullPath = $fileInfo->getRealPath();
        if ($fullPath === false) continue;

        $relative = substr($fullPath, strlen($baseDir) + 1);

        if ($relative === '' || str_contains($relative, '..')) continue;

        $result[] = $relative;
    }

    sort($result);
    return $result;
}

function createZipFromDir(string $baseDir): string
{
    if (!class_exists('ZipArchive')) {
        die("Extension ZIP non activée sur le serveur.");
    }

    $zipPath = tempnam(sys_get_temp_dir(), 'zip_');
    @unlink($zipPath);
    $zipPath .= '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        die('Impossible de créer le ZIP.');
    }

    $files = listProjectFiles($baseDir);

    foreach ($files as $relative) {
        $fullPath = $baseDir . DIRECTORY_SEPARATOR . $relative;

        if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'zip') continue;

        if (is_file($fullPath)) {
            $zip->addFile($fullPath, $relative);
        }
    }

    $zip->close();
    return $zipPath;
}

/* -------- TELECHARGEMENT ZIP -------- */

if (isset($_GET['download']) && $_GET['download'] === 'zip') {

    $zipPath = createZipFromDir($projetDir);
    $downloadName = 'projet_' . $projectId . '.zip';

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Cache-Control: no-store');

    readfile($zipPath);
    @unlink($zipPath);
    exit;
}

/* -------- PREPARATION AFFICHAGE -------- */

$files = listProjectFiles($projetDir);
$publicBase = '/projets_clients/' . $projectId . '/';

$siteIndex = null;
if (is_file($projetDir . '/index.html')) $siteIndex = $publicBase . 'index.html';
elseif (is_file($projetDir . '/index.php')) $siteIndex = $publicBase . 'index.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Livraison projet #<?= htmlspecialchars((string)$projectId) ?></title>

<style>
*{margin:0;padding:0;box-sizing:border-box;}

body{
    font-family:'Segoe UI',Arial,sans-serif;
    min-height:100vh;
    background:url('unique.png') no-repeat center center fixed;
    background-size:cover;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#2c2c2c;
}

.overlay{
    position:fixed;
    inset:0;
    background:rgba(255,255,255,0.35);
    backdrop-filter:blur(3px);
}

.container{
    position:relative;
    z-index:2;
    width:95%;
    max-width:850px;
    background:rgba(255,255,255,0.88);
    border-radius:18px;
    padding:45px;
    box-shadow:0 30px 80px rgba(0,0,0,0.15);
    border:1px solid rgba(200,180,150,0.4);
}

h1{
    font-size:28px;
    font-weight:500;
    margin-bottom:30px;
    letter-spacing:1px;
}

.buttons{
    margin-bottom:30px;
}

.btn{
    display:inline-block;
    padding:13px 22px;
    margin-right:12px;
    border-radius:8px;
    text-decoration:none;
    font-weight:500;
    transition:0.3s ease;
}

.btn-primary{
    background:#d6c4a3;
    color:#3a3325;
}

.btn-primary:hover{
    background:#c8b38f;
    transform:translateY(-2px);
}

.btn-secondary{
    background:white;
    border:1px solid #d6c4a3;
    color:#3a3325;
}

.btn-secondary:hover{
    background:#f6f1e8;
}

ul{
    list-style:none;
}

li{
    padding:14px 16px;
    margin-bottom:10px;
    background:#f9f6f0;
    border-radius:8px;
    transition:0.3s;
    border:1px solid #eee2cc;
}

li:hover{
    background:#f1eadf;
}

.file-link{
    text-decoration:none;
    color:#3a3325;
    font-weight:500;
}

.empty{
    opacity:0.7;
}

@media(max-width:600px){
    .container{
        padding:30px;
    }
}
</style>
</head>

<body>

<div class="overlay"></div>

<div class="container">

<h1>Livraison de votre projet #<?= htmlspecialchars((string)$projectId) ?></h1>

<div class="buttons">
    <a class="btn btn-primary" href="?id=<?= (int)$projectId ?>&download=zip">
        Télécharger le projet complet (.zip)
    </a>

    <?php if ($siteIndex): ?>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($siteIndex) ?>" target="_blank">
            Voir le site
        </a>
    <?php endif; ?>
</div>

<?php if (empty($files)): ?>
    <p class="empty">Aucun fichier n'a encore été déposé.</p>
<?php else: ?>
    <ul>
        <?php foreach ($files as $rel): ?>
            <li>
                <a class="file-link" href="<?= $publicBase . str_replace('%2F','/',rawurlencode($rel)) ?>" download>
                    <?= htmlspecialchars($rel) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

</div>
</body>
</html>
