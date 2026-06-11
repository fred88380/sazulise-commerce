<?php
// --- Logique moderne et robuste importée de sazulis news/pages/profil.php ---
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$name, $value] = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}
$bankName  = $_ENV['BANK_NAME'] ?? '';
$bankIban  = $_ENV['BANK_IBAN'] ?? '';
$bankBic   = $_ENV['BANK_BIC'] ?? '';
$bankOwner = $_ENV['BANK_OWNER'] ?? '';

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../security_headers.php';
require_once __DIR__ . '/../protect.php';
require_once __DIR__ . '/../paypal_init.php';
$paypalClientId = $paypalClientId ?? '';

if (!function_exists('e')) {
    function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("Erreur : \$pdo indisponible. Vérifie backend/db.php");
}
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$stripePublicKey = "pk_live_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";

// Structure connue de la table projets (d'après projets.sql)
$projectsTable   = 'projets';
$projetsUserCol  = 'id_utilisateur';

$has_total             = true;
$has_acompte           = true;
$has_solde             = true;
$has_acompte_recu      = true;
$has_contrat_signe     = true;
$has_avancement        = true;
$has_solde_regle       = true;
$has_code_livraison    = true;
$has_livraison_validee = true;
$has_created_at        = false;
$has_date_creation     = true;

$projectPaymentsEnabled = true;
$projectDeliveryEnabled = true;


$user = null;
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_profil']) || isset($_POST['update_avatar']))) {
        // Update profil
        if (isset($_POST['update_profil'])) {
            $nom   = trim((string)($_POST['nom'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $type  = (string)($_POST['type'] ?? 'particulier');
            if (!in_array($type, ['particulier', 'societe'], true)) $type = 'particulier';

            $adresse = null;
            $code_postal = null;
            $nom_societe = null;
            $siret = null;
            $adresse_societe = null;
            $code_postal_societe = null;

            if ($type === 'particulier') {
                $adresse = trim((string)($_POST['adresse'] ?? ''));
                $code_postal = trim((string)($_POST['code_postal'] ?? ''));
            } else {
                $nom_societe = trim((string)($_POST['nom_societe'] ?? ''));
                $siret = trim((string)($_POST['siret'] ?? ''));
                $adresse_societe = trim((string)($_POST['adresse_societe'] ?? ''));
                $code_postal_societe = trim((string)($_POST['code_postal_societe'] ?? ''));
            }

            if ($nom !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $stmt = $pdo->prepare(
                    'UPDATE utilisateurs
                     SET nom=?, email=?, statut=?, adresse=?, code_postal=?, nom_societe=?, siret=?, adresse_societe=?, code_postal_societe=?
                     WHERE id=?'
                );
                $stmt->execute([$nom, $email, $type, $adresse, $code_postal, $nom_societe, $siret, $adresse_societe, $code_postal_societe, $userId]);
            }
        }

        // Update avatar
        if (isset($_POST['update_avatar']) && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            $tmpName  = $_FILES['avatar']['tmp_name'];
            $origName = (string)$_FILES['avatar']['name'];
            $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if ($ext && in_array($ext, $allowedExt, true)) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, $tmpName) : null;
                if ($finfo) finfo_close($finfo);

                if ($mime && in_array($mime, $allowedMime, true)) {
                    $uploadDirFs = __DIR__ . '/../assets/img/avatars/';
                    if (!is_dir($uploadDirFs)) mkdir($uploadDirFs, 0777, true);

                    $newName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
                    $destFs  = $uploadDirFs . $newName;

                    if (move_uploaded_file($tmpName, $destFs)) {
                        $avatarPathWeb = '../assets/img/avatars/' . $newName;
                        $stmt = $pdo->prepare('UPDATE utilisateurs SET avatar=? WHERE id=?');
                        $stmt->execute([$avatarPathWeb, $userId]);
                    }
                }
            }
        }

        header("Location: " . $_SERVER['REQUEST_URI']);
        exit;
    }

    // Fetch user
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}

$acceptedProjects = [];
$pendingProjects  = [];
$finishedProjects = [];
$projectsForContracts = [];
$projectsError = null;

if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];

    if (false) { // table projets confirmée via projets.sql
        $projectsError = "La table 'projets' n'existe pas dans la base (vérifie la connexion DB).";
    } elseif (false) { // id_utilisateur confirmée via projets.sql
        $projectsError = "La table 'projets' n'a pas de colonne utilisateur (attendu: id_utilisateur).";
    } else {
        try {
            // EN COURS
            $stmt = $pdo->prepare("
                SELECT * FROM {$projectsTable}
                WHERE {$projetsUserCol} = ?
                  AND statut IN ('accepté','accepte')
                ORDER BY id DESC
            ");
            $stmt->execute([$uid]);
            $acceptedProjects = $stmt->fetchAll();

            // EN ATTENTE (inclut 'demande' = projet créé par admin mais pas encore accepté)
            $stmtP = $pdo->prepare("
                SELECT * FROM {$projectsTable}
                WHERE {$projetsUserCol} = ?
                  AND (
                       statut IN ('en_attente','attente','pending','en attente','demande')
                       OR statut LIKE '%attente%'
                  )
                ORDER BY id DESC
            ");
            $stmtP->execute([$uid]);
            $pendingProjects = $stmtP->fetchAll();

            // TERMINÉS
            if ($has_avancement && $has_solde_regle && $has_code_livraison) {
                $stmt2 = $pdo->prepare("
                    SELECT * FROM {$projectsTable}
                    WHERE {$projetsUserCol} = ?
                      AND (
                            statut IN ('transfere','transféré','termine','terminé')
                         OR (
                              (statut IS NULL OR statut = '')
                              AND avancement = 100
                              AND solde_regle = 1
                              AND code_livraison IS NOT NULL
                              AND code_livraison <> ''
                         )
                      )
                    ORDER BY id DESC
                ");
            } else {
                $stmt2 = $pdo->prepare("
                    SELECT * FROM {$projectsTable}
                    WHERE {$projetsUserCol} = ?
                      AND statut IN ('transfere','transféré','termine','terminé')
                    ORDER BY id DESC
                ");
            }
            $stmt2->execute([$uid]);
            $finishedProjects = $stmt2->fetchAll();

            // CONTRATS = tous
            $stmt3 = $pdo->prepare("
                SELECT * FROM {$projectsTable}
                WHERE {$projetsUserCol} = ?
                ORDER BY id DESC
            ");
            $stmt3->execute([$uid]);
            $projectsForContracts = $stmt3->fetchAll();

            // Validation livraison
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_livraison']) && $projectDeliveryEnabled) {
                $projectId = (int)($_POST['project_id'] ?? 0);
                if ($projectId > 0) {
                    $stmt4 = $pdo->prepare("UPDATE {$projectsTable} SET livraison_validee=1 WHERE id=? AND {$projetsUserCol}=?");
                    $stmt4->execute([$projectId, $uid]);
                    header("Location: " . $_SERVER['REQUEST_URI']);
                    exit;
                }
            }

        } catch (Throwable $e) {
            $projectsError = $e->getMessage();
            $acceptedProjects = [];
            $pendingProjects = [];
            $finishedProjects = [];
            $projectsForContracts = [];
        }
    }
}

$userFactures = [];
$facturesError = null;
if (isset($_SESSION['user_id'])) {
    try {
        $uid = (int)$_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT f.*
            FROM factures f
            INNER JOIN commandes c ON f.id_commande = c.id
            WHERE c.id_utilisateur = ?
            ORDER BY f.id DESC
        ");
        $stmt->execute([$uid]);
        $userFactures = $stmt->fetchAll();
    } catch (Throwable $e) {
        $facturesError = $e->getMessage();
        $userFactures = [];
    }
}

$nbProjets = count($acceptedProjects);
$nbPending = count($pendingProjects);
$nbFactures = count($userFactures);
$nbContrats = count($projectsForContracts);
$nbProjetsTermines = count($finishedProjects);

$paypalEnabled = !empty($paypalClientId);

$projectsData = [];
foreach (array_merge($acceptedProjects, $pendingProjects, $finishedProjects) as $p) {
    $id = (int)($p['id'] ?? 0);
    if ($id <= 0) continue;

    $total   = $has_total ? (float)($p['total'] ?? 0) : 0.0;
    $acompte = $has_acompte ? (float)($p['acompte'] ?? 0) : 0.0;
    $solde   = $has_solde ? (float)($p['solde'] ?? 0) : max(0, $total - $acompte);

    $date = '';
    if ($has_created_at) $date = (string)($p['created_at'] ?? '');
    elseif ($has_date_creation) $date = (string)($p['date_creation'] ?? '');

    $projectsData[$id] = [
        'id' => $id,
        'title' => $p['titre'] ?? ($p['nom'] ?? ('Projet #' . $id)),
        'statut' => (string)($p['statut'] ?? ''),
        'total' => $total,
        'acompte' => $acompte,
        'solde' => $solde,
        'created_at' => $date,
        'signatureUrl' => "../contrats/signature.php?projet_id=" . $id,
        'contratPdfUrl' => "../contrats/contrat.php?projet_id=" . $id,
        'acompte_recu' => $has_acompte_recu ? (int)($p['acompte_recu'] ?? 0) : 0,
        'contrat_signe' => $has_contrat_signe ? (int)($p['contrat_signe'] ?? 0) : 0,
        'avancement' => $has_avancement ? (int)($p['avancement'] ?? 0) : 0,
        'solde_regle' => $has_solde_regle ? (int)($p['solde_regle'] ?? 0) : 0,
        'code_livraison' => $has_code_livraison ? (string)($p['code_livraison'] ?? '') : '',
        'livraison_validee' => $has_livraison_validee ? (int)($p['livraison_validee'] ?? 0) : 0,
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<?php 
$head_base = '../';
include __DIR__ . '/../head.php'; 
?>
<style>
    body {
        background: url('../assets/img/unique.png') center/cover no-repeat fixed;
        margin: 0;
        font-family: 'Segoe UI', Arial, sans-serif;
    }
    main.profil-main {
        background: transparent !important;
        box-shadow: none !important;
        border-radius: 0;
        margin-bottom: 2em;
    }
    .hero-section {
        padding: 4em 0 2em 0;
        text-align: center;
    }
    .hero-section h1 {
        font-size: 2.7em;
        font-weight: 900;
        color: #1a2347;
        margin-bottom: 0.3em;
    }
    .hero-section .subtitle {
        font-size: 1.3em;
        color: #333;
        margin-bottom: 1.2em;
    }
    .hero-section .cta {
        display: inline-block;
        font-size: 1.15em;
        padding: 0.8em 2em;
        background: #1a2347;
        color: #fff;
        border-radius: 8px;
        text-decoration: none;
        margin-top: 1.2em;
    }
    /* === STATS CLIQUABLES === */
    .stats-section {
        display: flex; flex-wrap: wrap; justify-content: center;
        gap: 1.5em; max-width: 1000px; margin: 2em auto 2.5em auto; padding: 0 1em;
    }
    .stat-block {
        flex: 1 1 150px; min-width: 150px; max-width: 200px;
        background: rgba(255,255,255,0.97);
        border-radius: 16px; padding: 1.5em 1em;
        text-align: center;
        box-shadow: 0 2px 12px #1a234711;
        border: 2px solid #e0e7ff;
        text-decoration: none;
        cursor: pointer;
        transition: box-shadow .2s, transform .2s, border-color .2s;
    }
    .stat-block:hover {
        box-shadow: 0 6px 24px #1a234733;
        transform: translateY(-3px);
        border-color: #1a2347;
    }
    .stat-value { font-size: 2em; font-weight: 900; color: #1a2347; }
    .stat-label { color: #555; font-size: .95em; margin-top: .2em; }

    /* === PROFIL CONTAINER & GRID === */
    .profil-container { width: 100%; max-width: 1300px; margin: 2em auto 0 auto; padding: 0 1em; }
    .profil-grid-top {
        display: flex; gap: 2.5em; width: 100%; margin-bottom: 2.5em;
        flex-wrap: wrap; justify-content: center;
    }
    .profil-grid-bottom { display: flex; gap: 2.5em; width: 100%; align-items: flex-start; }
    .profil-bottom-left { flex: 2 1 0; display: flex; flex-direction: column; gap: 2em; width: 100%; }

    /* === CARDS === */
    .profil-card {
        background: rgba(255,255,255,0.97);
        border-radius: 20px;
        box-shadow: 0 4px 24px #1a234722, 0 2px 8px #0002;
        padding: 2em 1.5em 1.5em 1.5em;
        min-width: 270px; max-width: 360px; flex: 1 1 300px;
        display: flex; flex-direction: column; align-items: center;
        border: 2px solid #e0e7ff;
    }
    .profil-card.profile { align-items: center; text-align: center; min-width: 240px; }
    .profil-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; border: 3px solid #1a2347; margin-bottom: 1em; }
    .profil-card h2 { color: #1a2347; font-size: 1.3em; margin-bottom: .2em; }
    .profil-card .profil-type { color: #888; font-size: 1em; margin-bottom: 1em; }

    .profil-card .profil-btn {
        background: linear-gradient(90deg, #1a2347, #2e4080);
        color: #fff; border: none; border-radius: 10px; padding: .5em 1.3em;
        font-size: 1em; font-weight: 600; cursor: pointer; margin: .5em 0;
        box-shadow: 0 2px 8px #1a234733; transition: background .2s;
        text-decoration: none; display: inline-block;
    }
    .profil-card .profil-btn:hover { background: linear-gradient(90deg, #2e4080, #1a2347); }
    .profil-card .profil-btn.delete { background: #ffd7d7; color: #a00; box-shadow: none; }

    .profil-card.edit-form { align-items: flex-start; }
    .profil-card.edit-form form { width: 100%; display: flex; flex-direction: column; gap: 1em; }
    .profil-card.edit-form input, .profil-card.edit-form select {
        border-radius: 8px; border: 1.5px solid #1a234733; padding: .6em 1em;
        font-size: 1em; background: #f5f7ff;
    }
    .profil-card.edit-form input:focus, .profil-card.edit-form select:focus {
        outline: none; border-color: #1a2347; background: #fff;
    }

    /* === SECTIONS === */
    .profil-section { width: 100%; max-width: 1200px; margin: 0 auto 3.5em auto; }
    .profil-section h3 {
        color: #1a2347; font-size: 1.2em; font-weight: 900; text-align: center;
        margin-bottom: .8em; letter-spacing: 1px;
    }
    .profil-section.hidden-section { display: none; }
    .profil-section.hidden-section.active { display: block; }

    /* === PROJECT CARDS === */
    .projects-grid { display: grid; grid-template-columns: repeat(3, minmax(180px, 1fr)); gap: .8em; margin-top: 1em; }
    @media (max-width: 900px) { .projects-grid { grid-template-columns: 1fr; } }

    .project-card {
        background: rgba(255,255,255,0.97);
        border: 1px solid #e0e7ff;
        border-radius: 12px;
        box-shadow: 0 2px 8px #0001;
        padding: .8em .9em;
        display: flex; flex-direction: column; gap: .4em;
        cursor: pointer; min-width: 0; min-height: 110px;
        transition: box-shadow .15s, transform .15s;
    }
    .project-card:hover { box-shadow: 0 6px 20px #1a234733, 0 2px 8px #0002; transform: translateY(-2px) scale(1.02); }
    .project-title { font-weight: 900; color: #1a2347; font-size: 1.05em; }
    .project-meta { display: flex; gap: 1.2em; flex-wrap: wrap; color: #555; font-size: .95em; }

    /* === BTN MODAL ACTION === */
    .btn-modal-action {
        border: none; border-radius: 8px; padding: .6em 1.2em;
        font-size: 1em; font-weight: 700;
        background: linear-gradient(90deg, #1a2347, #2e4080);
        color: #fff; cursor: pointer;
        transition: opacity .18s, transform .15s;
        text-decoration: none; display: inline-flex; align-items: center; gap: .5em;
        box-shadow: 0 2px 8px #1a234733;
    }
    .btn-modal-action:hover { opacity: .85; transform: translateY(-1px); }

    /* === MODALS === */
    .modal-blur-bg {
        display: none; position: fixed; z-index: 9999;
        left: 0; top: 0; width: 100vw; height: 100vh;
        background: rgba(10,15,40,0.55);
        backdrop-filter: blur(3px);
        align-items: center; justify-content: center;
    }
    .modal-blur-bg.active { display: flex; }
    .modal-content-modern {
        background: #fff;
        padding: 2.2em 1.7em 2em 1.7em;
        max-width: 430px; width: 95vw;
        border-radius: 16px;
        position: relative;
        box-shadow: 0 8px 40px #1a234744;
        animation: modalIn .25s cubic-bezier(.4,2,.6,1) 1;
    }
    @keyframes modalIn { from { transform: translateY(40px) scale(.97); opacity: 0; } to { transform: none; opacity: 1; } }

    .modal-close-x {
        position: absolute; top: 12px; right: 18px; font-size: 2em;
        cursor: pointer; color: #1a2347; font-weight: 900; transition: .2s; line-height: 1;
    }
    .modal-close-x:hover { color: #c8902e; }

    .modal-title-modern {
        text-align: center; font-size: 1.3em; font-weight: 900; margin: 0 0 .5em 0;
        color: #1a2347; letter-spacing: 1px;
    }

    /* Project modal spécifique */
    .project-modal-modern { max-width: 540px; border-radius: 18px; padding: 0; overflow: hidden; border: 2px solid #e0e7ff; }
    .project-modal-header {
        background: linear-gradient(90deg, #1a2347 60%, #2e4080 100%);
        padding: 1.2em 1.7em 1em 1.7em;
        display: flex; align-items: center; justify-content: space-between;
        border-bottom: 1.5px solid #e0e7ff;
    }
    .project-modal-header .modal-title-modern { color: #fff !important; font-size: 1.2em; margin: 0; }
    .project-modal-header .modal-close-x { position: static; color: #fff; font-size: 1.8em; }
    .project-modal-header .modal-close-x:hover { color: #c8902e; }
    .project-modal-body { padding: 1.8em 1.7em 1.5em 1.7em; min-height: 120px; }
    .project-modal-infos {
        margin-bottom: 1.2em; font-size: 1.05em; color: #333;
        background: #f5f7ff; border-radius: 10px; padding: 1em 1.2em;
        box-shadow: 0 1px 6px #1a234711;
    }
    .project-modal-actions { display: flex; gap: 1em; flex-wrap: wrap; justify-content: flex-end; margin-top: 1.2em; }

    /* Modal paiement */
    .modal-acompte-info { text-align: center; font-size: 1.1em; margin-bottom: 1.2em; color: #222; font-weight: 500; }
    .pay-choice-row { display: flex; gap: 1em; justify-content: center; margin-bottom: 1.2em; }
    .pay-choice-btn {
        flex: 1 1 0;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        background: #f5f7ff; border: 2px solid #e0e7ff; border-radius: 10px;
        padding: 1em .4em; cursor: pointer; font-size: .95em; font-weight: 700;
        transition: .18s; color: #444; min-width: 90px; outline: none;
    }
    .pay-choice-btn.selected, .pay-choice-btn:hover { border-color: #1a2347; background: #e0e7ff; color: #1a2347; }
    .pay-choice-btn .pay-ico { font-size: 2em; margin-bottom: .2em; line-height: 1; }
    .modal-pay-details { margin-top: 1.5em; }

    @media (max-width: 700px) {
        .profil-grid-top { flex-direction: column; align-items: center; }
        .profil-card { max-width: 100%; min-width: 0; width: 100%; }
    }
</style>
<body>
<?php require_once __DIR__ . '/../navbar.php'; ?>
<main class="profil-main">

    <!-- === HERO SECTION (design profil.php) === -->
    <section class="hero-section">
        <h1>Espace client Sazulis</h1>
        <div class="subtitle">Gérez vos informations, suivez vos projets, factures et contrats.<br><span style="color:#c8902e;font-weight:600;">Espace sécurisé et personnalisé</span></div>
        <a href="/pages/products.php" class="cta">Voir la boutique</a>
    </section>

    <!-- === BOXES PROFIL + RENSEIGNEMENTS (logique profil2.php, design profil.php) === -->
    <div class="profil-container">
        <div class="profil-grid-top">

            <!-- Box Profil (avatar) -->
            <div class="profil-card profile">
                <img src="<?= isset($user['avatar']) && $user['avatar'] ? e($user['avatar']) : '../assets/img/avatar-default.png' ?>"
                     alt="Avatar" class="profil-avatar">
                <h2><?= isset($user['nom']) ? e($user['nom']) : '' ?></h2>
                <div class="profil-type"><?= isset($user['statut']) ? e($user['statut']) : '' ?></div>

                <form method="post" enctype="multipart/form-data" style="margin-bottom:1em;" id="avatarForm">
                    <label for="avatarInput" class="profil-btn" id="avatarLabel" style="margin-bottom:0.5em;cursor:pointer;display:inline-block;">
                        <?= e($langs['profil_change_avatar'] ?? "Changer l'image") ?>
                    </label>
                    <input type="file" id="avatarInput" name="avatar" accept="image/*" style="display:none;">
                    <input type="hidden" name="update_avatar" value="1">
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const avatarInput = document.getElementById('avatarInput');
                        const avatarForm  = document.getElementById('avatarForm');
                        const avatarLabel = document.getElementById('avatarLabel');
                        if (!avatarInput || !avatarForm || !avatarLabel) return;
                        avatarLabel.addEventListener('click', () => { avatarInput.value = ''; });
                        avatarInput.addEventListener('change', function() {
                            if (this.files && this.files.length > 0) {
                                avatarLabel.style.pointerEvents = 'none';
                                avatarLabel.style.opacity = '0.6';
                                avatarForm.submit();
                                setTimeout(() => {
                                    avatarInput.value = '';
                                    avatarLabel.style.pointerEvents = '';
                                    avatarLabel.style.opacity = '';
                                }, 2000);
                            }
                        });
                    });
                </script>

                <button class="profil-btn delete"><?= e($langs['profil_delete_account'] ?? "Supprimer le compte") ?></button>
            </div>

            <!-- Box Renseignements (formulaire infos) -->
            <div class="profil-card edit-form">
                <h2><?= e($langs['profil_my_info'] ?? "Mes informations") ?></h2>
                <form method="post" action="" autocomplete="off" id="profilForm">
                    <input type="text" name="nom" placeholder="<?= e($langs['profil_placeholder_name'] ?? "Nom") ?>"
                           value="<?= isset($user['nom']) ? e($user['nom']) : '' ?>" required>
                    <input type="email" name="email" placeholder="<?= e($langs['profil_placeholder_email'] ?? "Email") ?>"
                           value="<?= isset($user['email']) ? e($user['email']) : '' ?>" required>

                    <select name="type" id="typeSelect" required onchange="toggleSocieteFields()">
                        <option value="particulier" <?= (isset($user['statut']) && $user['statut'] === 'particulier') ? 'selected' : '' ?>>
                            <?= e($langs['profil_type_particulier'] ?? "Particulier") ?>
                        </option>
                        <option value="societe" <?= (isset($user['statut']) && $user['statut'] === 'societe') ? 'selected' : '' ?>>
                            <?= e($langs['profil_type_societe'] ?? "Société") ?>
                        </option>
                    </select>

                    <input type="text" name="adresse" id="adresseParticulier" placeholder="Adresse"
                           value="<?= isset($user['adresse']) ? e($user['adresse']) : '' ?>">
                    <input type="text" name="code_postal" id="codePostalParticulier" placeholder="Code postal"
                           value="<?= isset($user['code_postal']) ? e($user['code_postal']) : '' ?>">

                    <div id="societeFields" style="display:none; flex-direction:column; gap:0.7em;">
                        <input type="text" name="nom_societe" id="nomSociete" placeholder="Nom de la société"
                               value="<?= isset($user['nom_societe']) ? e($user['nom_societe']) : '' ?>">
                        <input type="text" name="siret" id="siret" placeholder="N° SIRET"
                               value="<?= isset($user['siret']) ? e($user['siret']) : '' ?>">
                        <input type="text" name="adresse_societe" id="adresseSociete" placeholder="Adresse de la société"
                               value="<?= isset($user['adresse_societe']) ? e($user['adresse_societe']) : '' ?>">
                        <input type="text" name="code_postal_societe" id="codePostalSociete" placeholder="Code postal de la société"
                               value="<?= isset($user['code_postal_societe']) ? e($user['code_postal_societe']) : '' ?>">
                    </div>

                    <button class="profil-btn" type="submit" name="update_profil"><?= e($langs['profil_update'] ?? "Mettre à jour") ?></button>
                </form>

                <script>
                    function toggleSocieteFields() {
                        const type = document.getElementById('typeSelect')?.value;
                        const societeFields = document.getElementById('societeFields');
                        const adresseParticulier = document.getElementById('adresseParticulier');
                        const codePostalParticulier = document.getElementById('codePostalParticulier');
                        if (!societeFields || !adresseParticulier || !codePostalParticulier) return;
                        if (type === 'societe') {
                            societeFields.style.display = 'flex';
                            adresseParticulier.style.display = 'none';
                            codePostalParticulier.style.display = 'none';
                            document.getElementById('nomSociete').required = true;
                            document.getElementById('siret').required = true;
                            document.getElementById('adresseSociete').required = true;
                            adresseParticulier.required = false;
                            codePostalParticulier.required = false;
                        } else {
                            societeFields.style.display = 'none';
                            adresseParticulier.style.display = 'block';
                            codePostalParticulier.style.display = 'block';
                            adresseParticulier.required = true;
                            codePostalParticulier.required = false;
                            document.getElementById('nomSociete').required = false;
                            document.getElementById('siret').required = false;
                            document.getElementById('adresseSociete').required = false;
                        }
                    }
                    document.addEventListener('DOMContentLoaded', toggleSocieteFields);
                </script>
            </div>

        </div><!-- /.profil-grid-top -->

        <!-- === STATS CLIQUABLES === -->
        <section class="stats-section">
            <a href="#projets" class="stat-block quick-link" data-target="projets">
                <div class="stat-value"><?= $nbProjets ?></div>
                <div class="stat-label">Projets en cours</div>
            </a>
            <a href="#projets" class="stat-block quick-link" data-target="projets">
                <div class="stat-value"><?= $nbPending ?></div>
                <div class="stat-label">En attente</div>
            </a>
            <a href="#projets-finis" class="stat-block quick-link" data-target="projets-finis">
                <div class="stat-value"><?= $nbProjetsTermines ?></div>
                <div class="stat-label">Projets terminés</div>
            </a>
            <a href="#factures" class="stat-block quick-link" data-target="factures">
                <div class="stat-value"><?= $nbFactures ?></div>
                <div class="stat-label">Factures</div>
            </a>
            <a href="#contrats" class="stat-block quick-link" data-target="contrats">
                <div class="stat-value"><?= $nbContrats ?></div>
                <div class="stat-label">Contrats</div>
            </a>
        </section>

        <!-- === SECTIONS PROJETS / FACTURES / CONTRATS === -->
        <div class="profil-grid-bottom">
            <div class="profil-bottom-left">

                <!-- PROJETS EN COURS + EN ATTENTE -->
                <div class="profil-section hidden-section" id="projets">
                    <h3>Projets</h3>

                    <?php if (!empty($projectsError)): ?>
                        <div class="notice error"><b>Erreur projets</b><br><small><?= e($projectsError) ?></small></div>
                    <?php else: ?>

                        <?php if (!empty($pendingProjects)): ?>
                            <div class="notice" style="border:2px solid #1a2347;background:#f0f3ff;margin-bottom:1em;">
                                <b>⏳ Projets en attente de validation</b><br>
                                <small>Ces projets apparaîtront "en cours" une fois acceptés.</small>
                            </div>
                            <div class="projects-grid" style="margin-bottom:1.5em;">
                                <?php foreach ($pendingProjects as $p): ?>
                                    <?php
                                        $projectTitle = $p['titre'] ?? $p['nom'] ?? ('Projet #' . ($p['id'] ?? ''));
                                        $projectId = (int)($p['id'] ?? 0);
                                        $statut = (string)($p['statut'] ?? 'en_attente');
                                        $total  = $has_total ? (float)($p['total'] ?? 0) : 0.0;
                                    ?>
                                    <div class="project-card" onclick="openProjectModal(<?= $projectId ?>)"
                                         style="border:2px dashed #1a2347;background:#f0f3ff;" title="Cliquez pour voir les détails">
                                        <div class="project-title"><?= e($projectTitle) ?></div>
                                        <div class="project-meta">
                                            <span>Statut: <b><?= e($statut) ?></b></span>
                                            <span>Total: <b><?= number_format($total, 2, ',', ' ') ?> €</b></span>
                                        </div>
                                        <div style="font-size:0.85em;color:#c8902e;margin-top:.2em;">⏳ En attente de validation admin.</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (count($acceptedProjects) === 0): ?>
                            <?php if (empty($pendingProjects)): ?>
                                <div class="notice" id="no-projects-card">
                                    Aucun projet pour le moment.<br>
                                    <small>Une fois validé dans le dashboard admin, il apparaîtra ici.</small>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="notice" style="margin-bottom:1em;">
                                <b>✅ Projets en cours</b><br>
                                <small>Clique sur un projet pour payer / signer / voir le contrat.</small>
                            </div>
                            <div class="projects-grid" style="max-height:60vh;overflow:auto;padding-bottom:1em;">
                                <?php foreach ($acceptedProjects as $p): ?>
                                    <?php
                                        $projectTitle = $p['titre'] ?? $p['nom'] ?? ('Projet #' . ($p['id'] ?? ''));
                                        $projectId    = (int)($p['id'] ?? 0);
                                        $total        = $has_total ? (float)($p['total'] ?? 0) : 0.0;
                                        $statut       = (string)($p['statut'] ?? '');
                                    ?>
                                    <div class="project-card" onclick="openProjectModal(<?= $projectId ?>)" title="Cliquez pour voir les détails du projet">
                                        <div class="project-title" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            <?= e($projectTitle) ?>
                                        </div>
                                        <div class="project-meta" style="gap:.5em;">
                                            <span>Statut: <b><?= e($statut) ?></b></span>
                                            <span>Total: <b><?= number_format($total, 2, ',', ' ') ?> €</b></span>
                                        </div>
                                        <div style="font-size:0.85em;color:#c8902e;margin-top:0.2em;opacity:0.85;display:flex;align-items:center;gap:0.3em;justify-content:center;">
                                            <span style="font-size:1.1em;">&#128072;</span> Cliquez pour accéder au paiement et au contrat
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>

                <!-- PROJETS TERMINÉS -->
                <div class="profil-section hidden-section" id="projets-finis" style="margin-top:2.5em;">
                    <h3 style="color:#2e7d4f;">Projets terminés</h3>

                    <?php if (empty($finishedProjects)): ?>
                        <div class="notice">Aucun projet terminé pour le moment.</div>
                    <?php else: ?>
                        <div class="projects-grid" style="max-height:40vh;overflow:auto;padding-bottom:1em;">
                            <?php foreach ($finishedProjects as $fp): ?>
                                <?php
                                    $projectTitle = $fp['titre'] ?? $fp['nom'] ?? ('Projet #' . ($fp['id'] ?? ''));
                                    $projectId    = (int)($fp['id'] ?? 0);
                                    $total        = $has_total ? (float)($fp['total'] ?? 0) : 0.0;
                                    $codeLivraison = $projectDeliveryEnabled ? ($fp['code_livraison'] ?? '') : '';
                                    $livraisonValidee = $projectDeliveryEnabled ? (int)($fp['livraison_validee'] ?? 0) : 0;
                                ?>
                                <div class="project-card" style="border:2px solid #2e7d4f;background:#f7fff7;cursor:default;">
                                    <div class="project-title" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= e($projectTitle) ?>
                                    </div>
                                    <div class="project-meta" style="gap:.5em;">
                                        <span>Statut: <b style="color:#2e7d4f;">Terminé</b></span>
                                        <span>Total: <b><?= number_format($total, 2, ',', ' ') ?> €</b></span>
                                    </div>

                                    <?php if ($projectDeliveryEnabled && $codeLivraison !== ''): ?>
                                        <div style="margin-top:0.7em;color:#116b38;font-weight:700;">
                                            Code de livraison :
                                            <span style="font-family:monospace;background:#eee;padding:2px 8px;border-radius:8px;">
                                                <?= e($codeLivraison) ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <a class="btn-modal-action" href="/telecharger_livraison.php?id=<?= $projectId ?>" target="_blank"
                                       style="margin-top:0.7em;background:#e7cf9c;color:#222;display:inline-block;">
                                        ⬇️ Télécharger la livraison
                                    </a>

                                    <?php if ($projectDeliveryEnabled): ?>
                                        <?php if ($livraisonValidee): ?>
                                            <div style="margin-top:0.7em;color:#2e7d4f;font-weight:700;">Livraison validée. Merci !</div>
                                        <?php else: ?>
                                            <form method="post" style="margin-top:1em;">
                                                <input type="hidden" name="validate_livraison" value="1">
                                                <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                                <button class="btn-modal-action" type="submit">✅ Valider la réception du projet</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FACTURES -->
                <div class="profil-section hidden-section" id="factures">
                    <h3><?= e($langs['profil_invoices_title'] ?? "Mes factures") ?></h3>

                    <?php if (!empty($facturesError)): ?>
                        <div class="notice error"><b>Erreur factures</b><br><small><?= e($facturesError) ?></small></div>
                    <?php elseif (empty($userFactures)): ?>
                        <div class="notice">Aucune facture pour le moment.</div>
                    <?php else: ?>
                        <div class="projects-grid">
                            <?php foreach ($userFactures as $f): ?>
                                <div class="project-card" style="border:2px solid #1a2347;background:#f0f3ff;cursor:default;">
                                    <div class="project-title" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        Facture <?= e($f['numero'] ?? ('#' . $f['id'])) ?>
                                    </div>
                                    <div class="project-meta" style="gap:.5em;">
                                        <span>Statut: <b><?= e($f['statut'] ?? '—') ?></b></span>
                                        <span>Total: <b><?= number_format((float)($f['montant'] ?? 0), 2, ',', ' ') ?> €</b></span>
                                    </div>
                                    <div class="project-meta" style="color:#888;">
                                        Date: <b><?= e($f['date_emission'] ?? $f['created_at'] ?? $f['date'] ?? '') ?></b>
                                    </div>
                                    <div style="color:#c8902e; font-size:0.95em; margin:0.5em 0;">Merci d'imprimer votre facture une fois le projet terminé</div>
                                    <div class="project-meta" style="margin-top:0.5em; gap:0.7em;">
                                        <a class="btn-modal-action" href="/pages/facture_pdf.php?id=<?= urlencode($f['id']) ?>" target="_blank" rel="noopener">📄 Ouvrir PDF</a>
                                        <a class="btn-modal-action" href="/factures/signature_facture.php?facture_id=<?= urlencode($f['id']) ?>" target="_blank" rel="noopener" style="background:#e0e7ff;color:#1a2347;">✍️ Signer</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CONTRATS -->
                <div class="profil-section hidden-section" id="contrats">
                    <h3><?= e($langs['profil_contracts_title'] ?? "Mes contrats") ?></h3>

                    <?php if (empty($projectsForContracts)): ?>
                        <div class="notice">Aucun contrat pour le moment.</div>
                    <?php else: ?>
                        <div class="projects-grid">
                            <?php foreach ($projectsForContracts as $p): ?>
                                <div class="project-card" style="border:2px solid #1a2347;background:#f0f3ff;cursor:default;">
                                    <div class="project-title" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        <?= e($p['titre'] ?? $p['nom'] ?? ('Projet #' . ($p['id'] ?? ''))) ?>
                                    </div>
                                    <div class="project-meta" style="gap:.5em;">
                                        <span>Statut : <b><?= e($p['statut'] ?? '—') ?></b></span>
                                    </div>
                                    <div class="project-meta" style="margin-top:0.5em; gap:0.7em;">
                                        <a class="btn-modal-action" href="/contrats/contrat.php?projet_id=<?= urlencode($p['id']) ?>" target="_blank" rel="noopener">📄 Voir le contrat</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div><!-- /.profil-bottom-left -->
        </div><!-- /.profil-grid-bottom -->

    </div><!-- /.profil-container -->

</main>

<!-- MODAL PROJET -->
<div id="projectModal" class="modal-blur-bg">
    <div class="modal-content-modern project-modal-modern">
        <div class="project-modal-header">
            <span class="modal-title-modern" id="projectModalTitle"></span>
            <span class="modal-close-x" onclick="closeProjectModal()" style="position:static;font-size:1.8em;">&times;</span>
        </div>
        <div class="project-modal-body" id="projectModalBody"></div>
    </div>
</div>

<!-- MODAL PAIEMENT -->
<div id="paymentModal" class="modal-blur-bg">
    <div class="modal-content-modern">
        <span class="modal-close-x" onclick="closePaymentModal()">&times;</span>
        <div class="modal-title-modern">Choisissez un mode de paiement</div>
        <div id="modalAcompteInfo" class="modal-acompte-info"></div>

        <div class="pay-choice-row">
            <button type="button" class="pay-choice-btn" id="payBtnStripe" onclick="selectPayment('stripe')">
                <span class="pay-ico">💳</span>
                <span class="pay-label">Payer par carte (Stripe)</span>
            </button>
            <button type="button" class="pay-choice-btn" id="payBtnPaypal" onclick="selectPayment('paypal')">
                <span class="pay-ico">🅿️</span>
                <span class="pay-label">Paypal</span>
            </button>
            <button type="button" class="pay-choice-btn" id="payBtnVirement" onclick="selectPayment('virement')">
                <span class="pay-ico">🏦</span>
                <span class="pay-label">Virement</span>
            </button>
        </div>
        <div id="paymentDetails" class="modal-pay-details"></div>
    </div>
</div>

<script>
/* === DATA PROJETS -> JS === */
const HAS_PROJECT_PAYMENTS = <?= json_encode($projectPaymentsEnabled) ?>;
const HAS_PROJECT_DELIVERY = <?= json_encode($projectDeliveryEnabled) ?>;
const projectsData = <?= json_encode($projectsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

window.openProjectModal = function(projectId) {
    const modal = document.getElementById('projectModal');
    const title = document.getElementById('projectModalTitle');
    const body  = document.getElementById('projectModalBody');
    const p = projectsData[projectId];
    if (!modal || !title || !body) return;
    if (!p) { alert('Projet introuvable (ID: ' + projectId + ')'); return; }

    title.textContent = p.title;

    const percent = Math.max(0, Math.min(100, Number(p.avancement || 0)));
    const readyForDev = (Number(p.acompte_recu) === 1 && Number(p.contrat_signe) === 1);

    const infosHtml = `
        <div class="project-modal-infos">
            <div style="display:flex;flex-wrap:wrap;gap:1.2em;align-items:center;">
                <span><b>Statut :</b> <span style="color:#1a2347;">${p.statut || '—'}</span></span>
                <span><b>Total :</b> ${Number(p.total).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})} €</span>
                <span><b>Acompte :</b> ${Number(p.acompte).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})} €</span>
                <span><b>Solde :</b> ${Number(p.solde).toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2})} €</span>
            </div>
            ${p.created_at ? `<div style="margin-top:.7em;color:#888;"><b>Créé le :</b> ${p.created_at}</div>` : ''}
        </div>
    `;

    if (!HAS_PROJECT_PAYMENTS) {
        body.innerHTML = infosHtml + `
            <div class="project-modal-actions">
                <a class="btn-modal-action" href="${p.signatureUrl}">✍️ Signer le contrat</a>
                <a class="btn-modal-action" href="${p.contratPdfUrl}" target="_blank" rel="noopener">📄 Voir le PDF</a>
            </div>`;
        modal.classList.add('active');
        return;
    }

    const isDelivered = (HAS_PROJECT_DELIVERY && p.code_livraison && (Number(p.solde_regle) === 1 || percent === 100));
    let html = '';

    if (isDelivered) {
        html = `
            <div class="project-modal-infos">
                <div style="color:#2e7d4f;font-weight:800;">✅ Livraison disponible</div>
                <div style="margin-top:.7em;">Code de livraison :
                    <span style="font-family:monospace;background:#eee;padding:2px 8px;border-radius:6px;">${p.code_livraison}</span>
                </div>
                <div style="margin-top:1em;">
                    <a class="btn-modal-action" href="/telecharger_livraison.php?id=${p.id}" target="_blank"
                       style="background:#e7cf9c;color:#222;margin-bottom:8px;display:inline-block;">⬇️ Télécharger la livraison</a><br/>
                    ${Number(p.livraison_validee) === 1
                        ? '<span style="color:#2e7d4f;font-weight:800;">Livraison validée. Merci !</span>'
                        : `<button class="btn-modal-action" type="button" onclick="validerLivraison(${p.id})">✅ Valider la réception du projet</button>`
                    }
                </div>
            </div>
            <div class="project-modal-actions">
                <a class="btn-modal-action" href="${p.contratPdfUrl}" target="_blank" rel="noopener">📄 Voir le PDF</a>
            </div>`;
    } else if (readyForDev) {
        html = `
            <div class="project-modal-infos">
                <div style="font-weight:800;color:#c8902e;">🚧 En cours</div>
                <div style="margin-top:.7em;"><b>Avancement :</b> ${percent}%</div>
                <div style="margin-top:.8em;display:flex;gap:.7em;flex-wrap:wrap;">
                    <button class="btn-modal-action" type="button" onclick="openPaymentModal(${p.id}, ${Number(p.solde)})">💳 Payer le solde</button>
                    <a class="btn-modal-action" href="${p.contratPdfUrl}" target="_blank" rel="noopener">📄 Voir le PDF</a>
                </div>
            </div>`;
    } else {
        html = `
            <div class="project-modal-actions">
                <button class="btn-modal-action" type="button" onclick="openPaymentModal(${p.id}, ${Number(p.acompte)})">💳 Payer l'acompte</button>
                <a class="btn-modal-action" href="${p.signatureUrl}">✍️ Signer le contrat</a>
                <a class="btn-modal-action" href="${p.contratPdfUrl}" target="_blank" rel="noopener">📄 Voir le PDF</a>
            </div>`;
    }

    body.innerHTML = infosHtml + html;
    modal.classList.add('active');
};

window.closeProjectModal = function() {
    document.getElementById('projectModal')?.classList.remove('active');
};

document.addEventListener('DOMContentLoaded', () => {
    const pm = document.getElementById('projectModal');
    if (pm) pm.addEventListener('click', (e) => { if (e.target === pm) closeProjectModal(); });
});

window.validerLivraison = function(projectId) {
    if (!confirm('Valider la réception du projet ?')) return;
    const form = document.createElement('form');
    form.method = 'POST'; form.style.display = 'none';
    const i1 = document.createElement('input'); i1.type='hidden'; i1.name='validate_livraison'; i1.value='1'; form.appendChild(i1);
    const i2 = document.createElement('input'); i2.type='hidden'; i2.name='project_id'; i2.value=projectId; form.appendChild(i2);
    document.body.appendChild(form);
    form.submit();
};
</script>

<script>
/* ---- Quick links : masque tout et affiche la section choisie ---- */
document.addEventListener("DOMContentLoaded", function() {
    const targets = ["projets", "projets-finis", "factures", "contrats"];

    function hideAll() {
        targets.forEach(id => document.getElementById(id)?.classList.remove("active"));
    }

    hideAll();
    document.getElementById("projets")?.classList.add("active");

    document.querySelectorAll(".quick-link").forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.preventDefault();
            const targetId = btn.dataset.target;
            hideAll();
            const section = document.getElementById(targetId);
            if (section) {
                section.classList.add("active");
                section.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    });
});
</script>

<script>
/* ---- Paiement ---- */
const STRIPE_PUBLIC_KEY = <?= json_encode($stripePublicKey) ?>;
const PAYPAL_ENABLED = <?= json_encode($paypalEnabled) ?>;

let currentProjectId = null;
let currentAcompte = 0;
let selectedPay = null;

function paypalFriendlyError(err) {
    const code = (err || '').toString();
    if (code.includes('acompte_already_paid')) return "L’acompte est déjà payé.";
    if (code.includes('solde_already_paid')) return "Le solde est déjà payé.";
    if (code.includes('project_not_found')) return "Projet introuvable.";
    if (code.includes('unauthorized')) return "Vous devez être connecté.";
    return code ? code : "Erreur PayPal.";
}

function openPaymentModal(projectId, montant) {
    if (!HAS_PROJECT_PAYMENTS) {
        alert("Le paiement n’est pas activé pour le moment.");
        return;
    }

    currentProjectId = Number(projectId);
    currentAcompte = Number(montant) || 0;

    document.getElementById('paymentModal')?.classList.add('active');
    document.getElementById('modalAcompteInfo').innerHTML =
        'Montant à payer : <b>' + currentAcompte.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €</b>';

    document.getElementById('paymentDetails').innerHTML = '';
    setSelectedPay(null);
}

function closePaymentModal() {
    document.getElementById('paymentModal')?.classList.remove('active');
    document.getElementById('paymentDetails').innerHTML = '';
    setSelectedPay(null);
}

document.addEventListener('DOMContentLoaded', () => {
    const payModal = document.getElementById('paymentModal');
    if (payModal) payModal.addEventListener('click', (e) => { if (e.target === payModal) closePaymentModal(); });
});

function setSelectedPay(type) {
    selectedPay = type;
    ['payBtnStripe','payBtnPaypal','payBtnVirement'].forEach(id => document.getElementById(id)?.classList.remove('selected'));
    if (type === 'stripe') document.getElementById('payBtnStripe')?.classList.add('selected');
    if (type === 'paypal') document.getElementById('payBtnPaypal')?.classList.add('selected');
    if (type === 'virement') document.getElementById('payBtnVirement')?.classList.add('selected');
}

function selectPayment(type) {
    setSelectedPay(type);

    let html = '';

    if (type === 'stripe') {
        html = `
            <div style="text-align:center;margin-top:1em;">
                <div style="margin-bottom:0.7em;">Montant à payer : <b>${currentAcompte.toLocaleString('fr-FR',{minimumFractionDigits:2, maximumFractionDigits:2})} €</b></div>
                <button id="stripeCheckoutBtn" class="btn-pay" style="font-size:1.1em;padding:.7em 2em;margin-top:.5em;">
                    Payer par carte (Stripe)
                </button>
                <div id="stripe-checkout-error" style="color:#a00;font-size:0.98em;margin-top:0.7em;"></div>
            </div>
        `;
    }

    if (type === 'paypal') {
        if (!PAYPAL_ENABLED) {
            html = `<div class="notice error">PayPal n’est pas configuré (client-id manquant).</div>`;
        } else {
            html = `
                <div style="text-align:center;margin-top:1em;">
                    <div style="margin-bottom:0.7em;">Montant à payer : <b>${currentAcompte.toLocaleString('fr-FR',{minimumFractionDigits:2, maximumFractionDigits:2})} €</b></div>
                    <div id="paypal-button-container" style="max-width:320px;margin:0 auto;"></div>
                    <div id="paypal-error" style="color:#a00;font-size:0.98em;margin-top:0.7em;"></div>
                </div>
            `;
        }
    }

    if (type === 'virement') {
        html = `
            <div style="margin-top:1em;text-align:left;">
                <b>Instructions pour le virement :</b><br><br>
                Montant : <b>${currentAcompte.toLocaleString('fr-FR',{minimumFractionDigits:2, maximumFractionDigits:2})} €</b><br><br>
                Banque : <?= e($bankName) ?><br>
                IBAN : <b><?= e($bankIban) ?></b><br>
                BIC : <?= e($bankBic) ?><br>
                Titulaire : <?= e($bankOwner) ?>
            </div>
        `;
    }

    document.getElementById('paymentDetails').innerHTML = html;

    if (type === 'paypal' && PAYPAL_ENABLED) renderPaypalButtons();
}

function renderPaypalButtons() {
    const container = document.getElementById('paypal-button-container');
    if (!container || typeof paypal === "undefined") return;

    container.innerHTML = "";

    let paymentType = 'acompte';
    if (currentAcompte && projectsData[currentProjectId] && Number(currentAcompte) === Number(projectsData[currentProjectId].solde)) {
        paymentType = 'solde';
    }

    paypal.Buttons({
        createOrder: function () {
            return fetch('../backend/paypal_create_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ project_id: currentProjectId, payment_type: paymentType })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.id) throw new Error(paypalFriendlyError(data.error_code || data.error || 'Order ID manquant'));
                return data.id;
            })
            .catch(err => {
                const el = document.getElementById('paypal-error');
                if (el) el.textContent = paypalFriendlyError(err.message);
                throw err;
            });
        },

        onApprove: function (data) {
            return fetch('../backend/paypal_capture_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ orderID: data.orderID, project_id: currentProjectId, payment_type: paymentType })
            })
            .then(r => r.json())
            .then(result => {
                if (result.status === 'COMPLETED') {
                    closePaymentModal();
                    window.location.reload();
                } else {
                    const el = document.getElementById('paypal-error');
                    if (el) el.textContent = "Paiement non finalisé : " + (result.status || "inconnu");
                }
            })
            .catch(() => {
                const el = document.getElementById('paypal-error');
                if (el) el.textContent = 'Erreur lors de la capture PayPal.';
            });
        },

        onError: function () {
            const el = document.getElementById('paypal-error');
            if (el) el.textContent = "Erreur PayPal. Réessaie.";
        }
    }).render('#paypal-button-container');
}

// Stripe checkout
document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'stripeCheckoutBtn') {
        let paymentType = 'acompte';
        if (currentAcompte && projectsData[currentProjectId] && Number(currentAcompte) === Number(projectsData[currentProjectId].solde)) {
            paymentType = 'solde';
        }

        fetch('../backend/stripe_pay.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ project_id: currentProjectId, payment_type: paymentType })
        })
        .then(r => r.json())
        .then(data => {
            if (data.checkoutUrl) window.location.href = data.checkoutUrl;
            else document.getElementById('stripe-checkout-error').textContent = data.error || 'Erreur Stripe.';
        })
        .catch(() => {
            document.getElementById('stripe-checkout-error').textContent = 'Erreur Stripe.';
        });
    }
});
</script>

<?php include '../footer.php'; ?>
</body>
</html>