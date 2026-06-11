<?php
session_start();

/* =========================
   MODE DEV (temporaire)
   ========================= */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* =========================
   LANG
   ========================= */
$lang = 'fr';
if (isset($_GET['lang']) && preg_match('~^[a-z]{2}$~', $_GET['lang'])) {
    $lang = $_GET['lang'];
    setcookie('siteLang', $lang, time() + (3600 * 24 * 30), "/");
} elseif (isset($_COOKIE['siteLang']) && preg_match('~^[a-z]{2}$~', $_COOKIE['siteLang'])) {
    $lang = $_COOKIE['siteLang'];
}

$langFile = __DIR__ . '/../lang/' . $lang . '.php';
if (!file_exists($langFile)) {
    $lang = 'fr';
    $langFile = __DIR__ . '/../lang/fr.php';
}
$langs = include $langFile;

/* =========================
   DB
   ========================= */
require_once __DIR__ . '/../backend/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    exit("Erreur DB: \$pdo indisponible (backend/db.php)");
}

/* =========================
   Detect colonne role
   ========================= */
$hasRole = false;
try {
    $cols = $pdo->query("SHOW COLUMNS FROM utilisateurs")->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasRole = in_array('role', $cols, true);
} catch (Throwable $e) {
    // si SHOW COLUMNS échoue, on continue sans role
    $hasRole = false;
}

/* =========================
   LOGIN
   ========================= */
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'])) {
    $email = trim((string)$_POST['email']);
    $password = (string)$_POST['password'];

    try {
        if ($hasRole) {
            $sql = "
                SELECT id, nom, email, mot_de_passe, role
                FROM utilisateurs
                WHERE LOWER(email) = LOWER(?)
                LIMIT 1
            ";
        } else {
            $sql = "
                SELECT id, nom, email, mot_de_passe
                FROM utilisateurs
                WHERE LOWER(email) = LOWER(?)
                LIMIT 1
            ";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && isset($user['mot_de_passe']) && password_verify($password, (string)$user['mot_de_passe'])) {
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_email'] = (string)$user['email'];
            $_SESSION['user_nom'] = (string)$user['nom'];

            // Si pas de colonne role => "user"
            $_SESSION['role'] = $hasRole ? ($user['role'] ?? 'user') : 'user';

            // Admins (toujours possible via email)
            if (
                ($hasRole && in_array($_SESSION['role'], ['admin', 'superadmin'], true)) ||
                strtolower($_SESSION['user_email']) === 'sazulis@outlook.fr'
            ) {
                header("Location: dashboard-admin.php");
                exit;
            }

            header("Location: profil.php");
            exit;
        }

        $errorMsg = $langs['login_error'] ?? "Identifiants incorrects";
    } catch (Throwable $e) {
        $errorMsg = "Erreur serveur : " . $e->getMessage();
    }
}

// navbar APRÈS le traitement
include '../navbar.php';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <?php 
    // 1. Mots-clés et balises uniques pour l'espace de connexion
    $page_title = ($langs['login'] ?? 'Connexion') . ' | Sazulis - Espace Client Sécurisé';
    $page_description = 'Connectez-vous à votre espace client Sazulis. Accès sécurisé pour suivre l\'avancement de vos projets web, SEO et gérer vos services.';

    // 2. Inclusion de ton head dynamique
    include __DIR__ . '/../head.php';
    ?>
    <style>
        body, html { height: 100%; margin: 0; }
        main { min-height: 100vh; display:flex; align-items:center; justify-content:center; position:relative; margin-top:-50px; margin-bottom:-100px; overflow:hidden; }
        main.connexion-main::before { content:""; position:absolute; inset:0; background:url('../assets/img/connexion.png') center/cover no-repeat scroll; z-index:-1; }
        .connexion-container { background: rgba(255,255,255,0.93); border-radius:32px; box-shadow: 0 8px 32px #ffd70055, 0 2px 8px #0002; padding: 2.5em 2em 2em; max-width:400px; width:100%; margin:2em 1em; display:flex; flex-direction:column; align-items:center; border:2px solid #ffe9c6; margin-top:-100px; position:relative; z-index:1000; }
        .connexion-container h1 { font-size:2em; color:#d4af37; margin-bottom:0.5em; font-family:'Montserrat',sans-serif; letter-spacing:1px; }
        .connexion-form { width:100%; display:flex; flex-direction:column; gap:1.2em; }
        .connexion-form input { border-radius:18px; border:1.5px solid #ffd70099; padding:0.8em 1.2em; font-size:1.05em; background:#fffbe6; box-shadow:0 1px 4px #ffd70022; outline:none; }
        .connexion-form button { background: linear-gradient(90deg, #ffe9c6, #fffbe6 80%, #ffd700); color:#333; border:none; border-radius:24px; padding:0.7em 2.2em; font-size:1.1em; font-weight:600; cursor:pointer; box-shadow:0 1px 8px #ffd70044; }
        .connexion-links { display:flex; gap:1em; justify-content:center; margin-top:1.5em; text-align:center; color:#888; font-size:1em; }
        .connexion-link-btn { background: linear-gradient(90deg, #ffe9c6, #fffbe6 80%, #ffd700); color:#d4af37; border:none; border-radius:18px; padding:0.5em 1.2em; font-size:1em; font-weight:500; cursor:pointer; box-shadow:0 1px 4px #ffd70022; }
        .password-field { position: relative; display:flex; align-items:center; }
        .password-field input { flex:1; }
        .toggle-password { width:32px; height:32px; cursor:pointer; margin-left:-38px; z-index:2; }
        .error-msg { color:red; text-align:center; margin-top:0.4em; }
    </style>
</head>
<body>
<main class="connexion-main">
    <div class="connexion-container">
        <h1><?= htmlspecialchars($langs['login_h1'] ?? ($langs['login'] ?? 'Connexion'), ENT_QUOTES, 'UTF-8') ?></h1>

        <form class="connexion-form" method="post" action="">
            <input type="email" name="email" placeholder="<?= htmlspecialchars($langs['login_placeholder_email'] ?? 'Votre email', ENT_QUOTES, 'UTF-8') ?>" required>
            <div class="password-field">
                <input type="password" name="password" id="password" placeholder="<?= htmlspecialchars($langs['login_placeholder_password'] ?? 'Votre mot de passe', ENT_QUOTES, 'UTF-8') ?>" required autocomplete="current-password">
                <img src="../assets/img/voir.png" alt="Afficher" class="toggle-password" data-target="password">
            </div>
            <button type="submit"><?= htmlspecialchars($langs['login_btn'] ?? 'Se connecter', ENT_QUOTES, 'UTF-8') ?></button>

            <?php if (!empty($errorMsg)): ?>
                <div class="error-msg"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </form>

        <div class="connexion-links">
            <button type="button" class="connexion-link-btn" onclick="window.location.href='motdepasse.php?lang=<?= htmlspecialchars($lang) ?>'">
                <?= htmlspecialchars($langs['login_forgot'] ?? 'Mot de passe oublié ?', ENT_QUOTES, 'UTF-8') ?>
            </button>
            <button type="button" class="connexion-link-btn" onclick="window.location.href='register.php?lang=<?= htmlspecialchars($lang) ?>'">
                <?= htmlspecialchars($langs['login_create'] ?? 'Créer un compte', ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
    </div>
</main>

<?php include '../footer.php'; ?>

<script>
document.querySelectorAll('.toggle-password').forEach(function(btn) {
  btn.addEventListener('click', function() {
    var input = document.getElementById(this.dataset.target);
    if (!input) return;
    if (input.type === 'password') {
      input.type = 'text';
      this.src = '../assets/img/cache.png';
      this.alt = 'Cacher';
    } else {
      input.type = 'password';
      this.src = '../assets/img/voir.png';
      this.alt = 'Afficher';
    }
  });
});
</script>
</body>
</html>
