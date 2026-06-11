<?php
$error = isset($error) ? (string) $error : '';
$basePath = isset($basePath) ? (string) $basePath : '';
?>
<section class="auth-wrap">
    <div class="catalog-head">
        <h1>Connexion client</h1>
        <p>Connecte-toi pour suivre tes commandes et accelerer le checkout.</p>
    </div>

    <form class="checkout-form" method="post" action="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/login">
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Mot de passe
            <input type="password" name="password" required>
        </label>
        <button class="btn btn-primary" type="submit">Se connecter</button>
        <?php if ($error !== ''): ?>
            <p class="feedback" style="color:#ff8d8d;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <p>Pas encore de compte ? <a class="link" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/register">Creer un compte</a></p>
    </form>
</section>
