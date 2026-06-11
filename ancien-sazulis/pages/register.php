<?php 
// Détection de la langue (fr, en, es)
$lang = 'fr';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr','en','es'], true)) {
    $lang = $_GET['lang'];
} elseif (isset($_COOKIE['siteLang']) && in_array($_COOKIE['siteLang'], ['fr','en','es'], true)) {
    $lang = $_COOKIE['siteLang'];
}

// Sécurise le fichier de langue
$langFile = __DIR__ . '/../lang/' . $lang . '.php';
if (!file_exists($langFile)) {
    $lang = 'fr';
    $langFile = __DIR__ . '/../lang/fr.php';
}

$langs = include $langFile;
setcookie('siteLang', $lang, time()+3600*24*30, '/');

include '../navbar.php';

$register_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nom'], $_POST['email'], $_POST['password'], $_POST['password_confirm'])) {
    $nom = trim((string)$_POST['nom']);
    $email = trim((string)$_POST['email']);
    $password = (string)$_POST['password'];
    $password_confirm = (string)$_POST['password_confirm'];

    // 1) validations
    if ($password !== $password_confirm) {
        $register_msg = '<div style="color:red;text-align:center;">' . ($langs['register_error_confirm'] ?? 'Les mots de passe ne correspondent pas.') . '</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_msg = '<div style="color:red;text-align:center;">' . ($langs['register_error_email'] ?? 'Email invalide.') . '</div>';
    } elseif (strlen($password) < 6) {
        $register_msg = '<div style="color:red;text-align:center;">Mot de passe trop court (6 caractères min).</div>';
    } else {
        // Validation stricte des emails autorisés
        $allowed_domains = ['@hotmail.com', '@hotmail.fr', '@outlook.com', '@outlook.fr', '@gmail.com', '@gmail.fr'];
        $email_lower = strtolower($email);
        $is_allowed = false;

        foreach ($allowed_domains as $domain) {
            if (str_ends_with($email_lower, $domain)) {
                $is_allowed = true;
                break;
            }
        }

        if (!$is_allowed) {
            $register_msg = '<div style="color:red;text-align:center;">Adresse email non autorisée. Utilisez Hotmail, Outlook ou Gmail.</div>';
        }
    }

    // 2) appel API UNIQUEMENT si pas d’erreur
    if ($register_msg === '') {
        $data = [
            'nom' => $nom,
            'email' => $email,
            'mot_de_passe' => $password
        ];

        $api_url =
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST']
            . rtrim(dirname($_SERVER['REQUEST_URI']), '/')
            . '/../backend/utilisateurs.php';

        // Nettoyage des doubles slash (sans casser https://)
        $api_url = preg_replace('#(?<!:)//+#','/', $api_url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            $register_msg = '<div style="color:red;text-align:center;">Erreur de connexion au serveur. Réessayez.</div>';
        } else {
            $json = json_decode((string)$response, true);

            if ($httpcode === 200 && is_array($json) && isset($json['success'])) {
                $register_msg = '<div style="color:green;text-align:center;">Votre compte a été créé, merci de vous connecter.</div>';
            } elseif ($httpcode === 409 && is_array($json) && ($json['error'] ?? '') === 'email_exists') {
                $register_msg = '<div style="color:red;text-align:center;">Cet email est déjà utilisé.</div>';
            } else {
                $register_msg = '<div style="color:red;text-align:center;">' . ($langs['register_error'] ?? 'Erreur lors de l\'inscription.') . '</div>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($langs['register_title'] ?? 'Inscription', ENT_QUOTES, 'UTF-8') ?> | Sazulis</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="description" content="Inscrivez-vous sur Sazulis pour accéder à votre espace client, gérer vos commandes et profiter de nos services web." />
    <meta name="keywords" content="inscription, register, sazulis, client, compte, web, services, création, boutique" />
    <link rel="icon" type="image/x-icon" href="../assets/img/sazulis-ico.ico">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body, html { height: 100%; margin: 0; }
        main.register-main {
            min-height: 100vh; display:flex; align-items:center; justify-content:center;
            position:relative; margin-top:-50px; margin-bottom:-100px; overflow:hidden;
        }
        main.register-main::before {
            content:""; position:absolute; top:0; left:0; right:0; bottom:0;
            background:url('../assets/img/connexion.png') center/cover no-repeat scroll;
            z-index:-1;
        }
        .register-container {
            background: rgba(255,255,255,0.93);
            border-radius: 32px;
            box-shadow: 0 8px 32px #ffd70055, 0 2px 8px #0002;
            padding: 2.5em 2em 2em;
            max-width: 420px; width: 100%;
            margin: 2em 1em;
            display:flex; flex-direction:column; align-items:center;
            border: 2px solid #ffe9c6;
            margin-top:-100px; position:relative; z-index:1;
        }
        .register-container h1 { font-size:2em; color:#d4af37; margin-bottom:0.5em; font-family:'Montserrat',sans-serif; letter-spacing:1px; }
        .register-form { width:100%; display:flex; flex-direction:column; gap:1.2em; }
        .register-form input {
            border-radius:18px; border:1.5px solid #ffd70099;
            padding:0.8em 1.2em; font-size:1.05em; background:#fffbe6;
            box-shadow:0 1px 4px #ffd70022; outline:none;
        }
        .password-field { position:relative; display:flex; align-items:center; }
        .password-field input { flex:1; }
        .toggle-password { width:32px; height:32px; cursor:pointer; margin-left:-38px; z-index:2; }
        .register-form button {
            background: linear-gradient(90deg, #ffe9c6, #fffbe6 80%, #ffd700);
            color:#333; border:none; border-radius:24px;
            padding:0.7em 2.2em; font-size:1.1em; font-weight:600;
            cursor:pointer; box-shadow:0 1px 8px #ffd70044;
        }
        .register-links { margin-top:1.5em; text-align:center; color:#888; font-size:1em; }
        .register-links a { color:#d4af37; text-decoration:none; margin:0 0.5em; font-weight:500; }
        @media (max-width: 600px) {
            .register-container { padding:1.2em 0.5em; }
            .register-container h1 { font-size:1.2em; }
        }
    </style>
</head>
<body>
<main class="register-main">
    <div class="register-container">
        <h1><?= htmlspecialchars($langs['register_h1'] ?? 'Créer un compte', ENT_QUOTES, 'UTF-8') ?></h1>

        <form class="register-form" method="post" action="">
            <input type="text" name="nom" placeholder="<?= htmlspecialchars($langs['register_placeholder_name'] ?? 'Votre nom', ENT_QUOTES, 'UTF-8') ?>" required>
            <input type="email" name="email" placeholder="<?= htmlspecialchars($langs['register_placeholder_email'] ?? 'Votre email', ENT_QUOTES, 'UTF-8') ?>" required>

            <div class="password-field">
                <input type="password" name="password" id="password" placeholder="<?= htmlspecialchars($langs['register_placeholder_password'] ?? 'Mot de passe', ENT_QUOTES, 'UTF-8') ?>" required>
                <img src="../assets/img/voir.png" alt="Afficher" class="toggle-password" data-target="password">
            </div>

            <div class="password-field">
                <input type="password" name="password_confirm" id="password_confirm" placeholder="<?= htmlspecialchars($langs['register_placeholder_confirm'] ?? 'Confirmer', ENT_QUOTES, 'UTF-8') ?>" required>
                <img src="../assets/img/voir.png" alt="Afficher" class="toggle-password" data-target="password_confirm">
            </div>

            <button type="submit"><?= htmlspecialchars($langs['register_btn'] ?? 'S’inscrire', ENT_QUOTES, 'UTF-8') ?></button>
        </form>

        <?php if ($register_msg) echo $register_msg; ?>

        <div class="register-links">
            <a href="connexion.php?lang=<?= htmlspecialchars($lang) ?>">
                <?= htmlspecialchars($langs['register_already'] ?? 'Déjà un compte ?', ENT_QUOTES, 'UTF-8') ?>
                <?= htmlspecialchars($langs['register_login_link'] ?? 'Se connecter', ENT_QUOTES, 'UTF-8') ?>
            </a>
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
